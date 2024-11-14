import os
import time
def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

set_affinity(2)

import joblib
import re

import requests
import logging
    

# Load the classification model
model_path = "text_classification_model_nb.pkl"
classifier_model = joblib.load(model_path)

# Define the path to the keystroke log file
keystroke_log_path = "keystroke_log.log"

# Define the interval in seconds to check for new data
poll_interval = 10

# Set up logging
logging.basicConfig(level=logging.DEBUG, format='%(asctime)s - %(levelname)s - %(message)s')

def clear_keystrokes():
    open(keystroke_log_path, "w").close()

def send_data_to_server(data):
    url = "http://10.0.0.1/store_data"
    headers = {'Content-Type': 'application/json'}
    try:
        response = requests.post(url, json=data, headers=headers)
        logging.debug(f"Response status code: {response.status_code}")
    except requests.RequestException as e:
        logging.error(f"Request failed: {e}")
        time.sleep(5)

def process_new_keystrokes(log_path, start_pos, chunk_size=50, overlap_size=25):
    """Process new keystrokes from the log file starting from a given position."""
    try:
        with open(log_path, 'r', encoding='utf-8') as log_file:
            log_file.seek(start_pos)
            lines = log_file.readlines()

            valid_characters = []
            new_phrases = []
            copy_count = 0
            paste_count = 0
            total_keystrokes = 0

            for line in lines:
                parts = line.strip().split('","')
                if len(parts) != 3:
                    continue

                typed_key = parts[0].strip('"').replace("[", "").replace("]", "")
                total_keystrokes += 1

                # Check for Ctrl+C (Copy)
                if typed_key == 'Ctrlc' or typed_key == 'CtrlC':
                    copy_count += 1

                # Check for Ctrl+V (Paste)
                if typed_key == 'Ctrlv' or typed_key == 'CtrlV':
                    paste_count += 1

                # Handle normal keys and add them to valid characters
                if typed_key in ['SpaceBar', 'Key.space']:
                    valid_characters.append(' ')
                elif re.match(r'^[a-zA-Z0-9]$', typed_key):  # Only allow alphanumeric characters
                    valid_characters.append(typed_key)
                
                # When we have enough characters, form a phrase
                while len(valid_characters) >= chunk_size:
                    phrase = ''.join(valid_characters[:chunk_size]).strip()
                    new_phrases.append(phrase)

                    # Retain the last `overlap_size` characters for the next chunk
                    valid_characters = valid_characters[chunk_size - overlap_size:]

            # Debug summary after processing
            logging.debug(f"Total Copy Actions: {copy_count}, Total Paste Actions: {paste_count}")

            return new_phrases, copy_count, paste_count, total_keystrokes, log_file.tell()
    except Exception as e:
        logging.error(f"Error reading keystrokes: {e}")
        return [], 0, 0, 0, start_pos

def classify_phrases(phrases):
    """Classify and send alerts for each phrase labeled as potential cheating."""
    for phrase in phrases:
        prediction = classifier_model.predict([phrase])
        logging.debug(f"Phrase Flagged for Possible Cheating: {phrase}")

        # Send to server if classified as potential cheating (label 1)
        if prediction == 1:
            send_data_to_server({
                'type': 'Keystroke',
                'content': f"Phrase: {phrase} => potential cheating detected"
            })



def main():
    last_position = 0
    total_copy_count = 0
    total_paste_count = 0
    total_keystrokes = 0
    threshold = 0.2  # Define the threshold for copy-paste flagging
    
    time.sleep(600) # wait for other services to start
    while True:
        new_phrases, copy_count, paste_count, keystrokes, last_position = process_new_keystrokes(
            keystroke_log_path, last_position, chunk_size=50, overlap_size=25
        )

        # Update counts
        total_copy_count += copy_count
        total_paste_count += paste_count
        total_keystrokes += keystrokes

        # Classify new phrases if any
        if new_phrases:
            classify_phrases(new_phrases)
            print(new_phrases)

        # Calculate and print copy-paste ratio
        total_copy_paste = total_copy_count + total_paste_count
        copy_paste_ratio = total_copy_paste / total_keystrokes if total_keystrokes else 0
        logging.debug(f"Copy actions: {total_copy_count}, Paste actions: {total_paste_count}")
        logging.debug(f"Total keystrokes: {total_keystrokes}")
        logging.debug(f"Copy-Paste Ratio: {copy_paste_ratio:.2%}")

        # Flag and send to server if copy-paste ratio exceeds threshold
        if copy_paste_ratio > threshold:
            send_data_to_server({
                'type': 'Keystroke',
                'content': "Warning: High frequency of copy-paste actions detected."
            })

        # Pause before rechecking for new keystrokes
        time.sleep(poll_interval)

if __name__ == "__main__":
    clear_keystrokes()
    main()
