import os
def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

set_affinity(2)

import time
import platform
import subprocess
import logging
import base64
import requests
import joblib
from shutil import move, rmtree
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
import threading

# Import necessary functions
from pytess_ocr import ocr_image, clean_text
from matching import run_template_match
from roi import generate_roi

logging.basicConfig(level=logging.DEBUG)

class ImageHandler(FileSystemEventHandler):
    def __init__(self, processor, input_folder):
        self.processor = processor
        self.input_folder = input_folder

    def on_created(self, event):
        if not event.is_directory and event.src_path.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
            print(f"New image detected: {event.src_path}")
            self.processor.process_image(event.src_path)

class ImageProcessor:
    def __init__(self, input_folder, roi_output_folder, completed_folder, model_path="text_classification_model_nb.pkl"):
        self.input_folder = input_folder
        self.roi_output_folder = roi_output_folder
        self.completed_folder = completed_folder
        os.makedirs(self.roi_output_folder, exist_ok=True)
        os.makedirs(self.completed_folder, exist_ok=True)
        self.model = joblib.load(model_path)  # Load the classification model once at startup
        self.all_results = {}

    def encode_image_to_base64(self, image_path):
        with open(image_path, "rb") as image_file:
            return base64.b64encode(image_file.read()).decode('utf-8')

    def send_data_to_server(self, data):
        url = "http://10.0.0.1/store_data"
        headers = {'Content-Type': 'application/json'}
        try:
            response = requests.post(url, json=data, headers=headers)
            logging.debug(f"Response status code: {response.status_code}")
        except requests.RequestException as e:
            logging.error(f"Request failed: {e}")
            time.sleep(5)

    def process_image(self, image_path):
        file_name = os.path.splitext(os.path.basename(image_path))[0]
        logging.info(f"Processing image: {image_path}")

        # Generate ROIs
        _, num_rois = generate_roi(image_path, self.roi_output_folder)
        self.all_results[file_name] = {
            'original_image': image_path,
            'rois': [],
            'template_matching': {},
            'conversation_percentage': 0
        }

        # Template Matching
        result = run_template_match(image_path)
        self.all_results[file_name]['template_matching'] = result

        # OCR and Classification for each ROI
        folder_path = os.path.join(self.roi_output_folder, file_name)
        conversation_count = 0
        total_rois = 0
        for roi_name in os.listdir(folder_path):
            if roi_name.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
                roi_path = os.path.join(folder_path, roi_name)
                extracted_text = ocr_image(roi_path)
                cleaned_text = clean_text(extracted_text)
                classification = "conversation" if self.model.predict([cleaned_text])[0] == 1 else "others"
                self.all_results[file_name]['rois'].append({
                    'roi_image': roi_path,
                    'extracted_text': cleaned_text,
                    'classification': classification
                })
                if classification == "conversation":
                    conversation_count += 1
                total_rois += 1

        # Calculate conversation percentage
        conversation_percentage = (conversation_count / total_rois) * 100 if total_rois > 0 else 0
        self.all_results[file_name]['conversation_percentage'] = conversation_percentage

        # Check for high-risk content
        threshold = 0.7
        any_high_risk_templates = any(value > threshold for value in result.values() if isinstance(value, float))

        if conversation_percentage > 70 or any_high_risk_templates:
            base64_image = self.encode_image_to_base64(image_path)
            self.send_data_to_server({'type': 'Screenshot image', 'content': base64_image})

            if conversation_percentage > 70:
                self.send_data_to_server({
                    'type': f"Conversation percentage: {file_name}",
                    'content': f"conversation_percentage={conversation_percentage:.2f}"
                })

            if any_high_risk_templates:
                matched_templates = [key.replace('_value', '') for key, value in result.items() if value > threshold]
                self.send_data_to_server({
                    'type': "Templates matched",
                    'content': f"{','.join(matched_templates)}"
                })

        # Move processed image to completed folder
        move(image_path, os.path.join(self.completed_folder, os.path.basename(image_path)))
        logging.info(f"Image processing complete: {image_path}")

def clear_folder(folder_path):
    """Clears all files in the specified folder at startup."""
    for filename in os.listdir(folder_path):
        file_path = os.path.join(folder_path, filename)
        if os.path.isfile(file_path):
            os.remove(file_path)

if __name__ == "__main__":
    input_folder = '/home/raspberry/flaskserver/images'
    roi_output_folder = './roi_results'
    completed_folder = './completed'

    # Ensure necessary directories exist
    os.makedirs(input_folder, exist_ok=True)
    os.makedirs(completed_folder, exist_ok=True)

    # Clear old files in input and completed folders
    clear_folder(input_folder)
    clear_folder(completed_folder)

    # Create the processor and start the observer
    processor = ImageProcessor(input_folder, roi_output_folder, completed_folder)
    event_handler = ImageHandler(processor, input_folder)
    observer = Observer()
    observer.schedule(event_handler, path=input_folder, recursive=False)
    

    # Start periodic cleanup in a separate thread
    cleanup_thread = threading.Thread(target=lambda: clear_folder(completed_folder))
    cleanup_thread.daemon = True
  
    time.sleep(550) #wait for webcam and audio to start
    observer.start()
    cleanup_thread.start()
    
    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        observer.stop()
    observer.join()
