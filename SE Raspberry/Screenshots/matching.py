import cv2
import os
import pandas as pd

# Directories
template_folder = "./templates"

# Function to perform template matching
def run_template_match(image_path):
    source = cv2.imread(image_path, 0)
    results = {"image_name": os.path.basename(image_path)}
    matching_values = {}

    for template in os.listdir(template_folder):
        if template.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
            template_path = os.path.join(template_folder, template)
            curr_template = cv2.imread(template_path, 0)
            match, value = template_match(source, curr_template)
            results[f"{template[:-4]}"] = match
            matching_values[f"{template[:-4]}_value"] = value

    return {**results, **matching_values}

def template_match(image, template):
    res = cv2.matchTemplate(image, template, cv2.TM_CCOEFF_NORMED)
    min_val, max_val, min_loc, max_loc = cv2.minMaxLoc(res)
    threshold = 0.7
    return max_val >= threshold, max_val

# Only for standalone testing, remove or comment out if not needed
# if __name__ == "__main__":
#     input_folder = './images2process'
#     # Process all images in the input folder
#     template_matching_results = []

#     for image_name in os.listdir(input_folder):
#         if image_name.lower().endswith(('.png', '.jpg', '.jpeg', '.bmp', '.tiff')):
#             image_path = os.path.join(input_folder, image_name)

#             # Perform template matching
#             result = run_template_match(image_path)

#             # Log template matching results
#             template_matching_results.append(result)

#     # Convert results to DataFrame
#     template_matching_df = pd.DataFrame(template_matching_results)

#     # Write DataFrame to CSV
#     template_matching_csv_file = 'template_matching_results.csv'
#     template_matching_df.to_csv(template_matching_csv_file, index=False)

#     print(f"Template matching results have been written to {template_matching_csv_file}")
