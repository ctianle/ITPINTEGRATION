import os
import joblib
import pandas as pd
import re
import pytesseract


# Load the pre-trained model
model = joblib.load('text_classification_model_nb.pkl')

# Function to perform OCR using pytesseract
def ocr_image(image_path):
    text = pytesseract.image_to_string(image_path, config='--psm 6')
    return text.strip()

# Function to clean text
def clean_text(text):
    # Remove special characters but keep letters, numbers, spaces, apostrophes, and common punctuation
    text = re.sub(r"[^a-zA-Z0-9\s'.,:;!?(){}[\]\-]", '', text)
    text = text.lower()
    text = text.strip()
    text = re.sub(r'\s+', ' ', text)  # Replace multiple spaces with a single space
    return text
