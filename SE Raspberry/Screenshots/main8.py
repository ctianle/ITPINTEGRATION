import os
from pytess_ocr import ocr_image, clean_text, model
from matching import run_template_match
from roi import generate_roi
import subprocess
import platform
import requests
import time
import logging
import base64

logging.basicConfig(level=logging.DEBUG)

def set_affinity(core_id):
    if platform.system() == 'Linux':
        command = f"taskset -cp {core_id} {os.getpid()}"
        subprocess.run(command, shell=True)

input_folder = './processing'
roi_output_folder = './roi_results'
completed_folder = './completed'
os.makedirs(roi_output_folder, exist_ok=True)

roi_results = []
template_matching_results = []
all_results = {}

def encode_image_to_base64(image_path):
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode('utf-8')

def send_data_to_server(data):
    url = "http://10.0.0.1/store_data"
    headers = {'Content-Type': 'application/json'}
    try:
        response = requests.post(url, json=data, headers=headers)
        logging.debug(f"Response status code: {response.status_code}")
    except requests.RequestException as e:
        logging.error(f"Request failed: {e}")
        time.sleep(5)

def process_rois():
    logging.info("Starting ROI generation...")
    for image_name in os.listdir(input_folder):
        if image_name.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
            image_path = os.path.join(input_folder, image_name)
            logging.info(f"Generating ROIs for {image_name}...")
            file_name, num_rois = generate_roi(image_path, roi_output_folder)
            roi_results.append({'image_name': image_name, 'num_rois': num_rois})
            all_results[file_name] = {
                'original_image': image_path,
                'rois': [],
                'template_matching': {},
                'conversation_percentage': 0
            }
            logging.info(f"Generated {num_rois} ROIs for {image_name}.")

def process_template_matching():
    logging.info("Starting template matching...")
    for image_name in os.listdir(input_folder):
        if image_name.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
            image_path = os.path.join(input_folder, image_name)
            logging.info(f"Performing template matching for {image_name}...")
            result = run_template_match(image_path)
            template_matching_results.append(result)
            file_name = os.path.splitext(os.path.basename(image_path))[0]
            if file_name in all_results:
                all_results[file_name]['template_matching'] = result
            else:
                logging.warning(f"{file_name} not found in all_results during template matching.")
            logging.info(f"Template matching completed for {image_name}.")

def process_ocr_and_classification():
    logging.info("Starting OCR and text classification...")
    for folder_name in os.listdir(roi_output_folder):
        folder_path = os.path.join(roi_output_folder, folder_name)
        if os.path.isdir(folder_path):
            logging.info(f"Processing OCR for folder {folder_name}...")
            results = []
            for image_name in os.listdir(folder_path):
                if image_name.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
                    image_path = os.path.join(folder_path, image_name)
                    if not os.path.exists(image_path):
                        logging.warning(f"File {image_path} does not exist. Skipping...")
                        continue
                    logging.info(f"Performing OCR on {image_name} in folder {folder_name}...")
                    extracted_text = ocr_image(image_path)
                    cleaned_text = clean_text(extracted_text)

                    classification = "others" if cleaned_text == "" else model.predict([cleaned_text])[0]
                    classification = "conversation" if classification == 1 else "others"
                    
                    logging.info(f"{image_name}: Classified as {classification}.")
                    results.append({
                        'roi_image': image_path,
                        'extracted_text': cleaned_text,
                        'classification': classification
                    })
                    if folder_name in all_results:
                        all_results[folder_name]['rois'].append({
                            'roi_image': image_path,
                            'extracted_text': cleaned_text,
                            'classification': classification
                        })
                    else:
                        logging.warning(f"{folder_name} not found in all_results during OCR processing.")

            if folder_name not in all_results:
                all_results[folder_name] = {
                    'original_image': None,
                    'rois': [],
                    'template_matching': {},
                    'conversation_percentage': 0
                }

            conversation_count = sum(1 for roi in all_results[folder_name]['rois'] if roi['classification'] == "conversation")
            total_rois = len(all_results[folder_name]['rois'])
            conversation_percentage = (conversation_count / total_rois) * 100 if total_rois > 0 else 0
            all_results[folder_name]['conversation_percentage'] = conversation_percentage

            threshold = 0.7
            any_high_risk_templates = any(value > threshold for key, value in all_results[folder_name]['template_matching'].items() if key.endswith('_value') and value)

            # Send data to server if conditions are met
            if conversation_percentage > 70 or any_high_risk_templates:
                base64_image = encode_image_to_base64(all_results[folder_name]['original_image'])
                send_data_to_server({
                    'uuid': '',
                    'type': f'Screenshot image: {folder_name}',
                    'content': f"{base64_image}"
                })

                if conversation_percentage > 70:
                    send_data_to_server({
                        'uuid': '',
                        'type': f"Conversation percentage: {folder_name}",
                        'content': f"conversation_percentage={conversation_percentage:.2f}"
                    })

                if any_high_risk_templates:
                    # Extracting templates that match
                    matched_templates = [key for key, value in all_results[folder_name]['template_matching'].items() if key.endswith('_value') and value > threshold]
                    matched_templates = [key.replace('_value', '') for key in matched_templates]
                    send_data_to_server({
                        'uuid': '',
                        'type': f"Templates matched: {folder_name}",
                        'content': f"{','.join(matched_templates)}"
                    })

    logging.info("All processing completed.")

if __name__ == "__main__":
    set_affinity(1)

    logging.info(f"Processing images in: {input_folder}")
    process_rois()
    process_template_matching()
    process_ocr_and_classification()
