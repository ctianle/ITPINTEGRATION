import os
def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 0
set_affinity(0)

import pyaudio
import struct
import numpy as np
from scipy.fftpack import fft, fftfreq
from scipy.signal import butter, sosfilt, find_peaks
import threading
import requests
import time
import vosk
import json
import sounddevice
from queue import Queue

url = "http://10.0.0.1/store_data"


# Parameters
BUFFER = 1024  # samples per frame
FORMAT = pyaudio.paInt16  # audio format
CHANNELS = 1  # single channel for microphone
RATE = 16000  # samples per second

def design_filter(lowfreq, highfreq, fs, order=3):
    nyq = 0.5 * fs
    low = lowfreq / nyq
    high = highfreq / nyq
    sos = butter(order, [low, high], btype='band', output='sos')
    return sos

# Design the filter
sos = design_filter(75, 300, RATE, 3)

# Initialize the pyaudio class instance
audio = pyaudio.PyAudio()
stream = audio.open(
    rate=RATE,
    format=FORMAT,
    channels=CHANNELS,
    input=True,
    input_device_index=0)
    
frequency_buffer = []
moving_sample_size = 2
recording_time = 0
vosk_model_path = "vosk-model-small-en-us-0.15"
if not os.path.exists(vosk_model_path):
    print("Please download the model from https://alphacephei.com/vosk/models and unpack as 'model' in the current folder.")
    exit(1)
    
model = vosk.Model(vosk_model_path)
recognizer = vosk.KaldiRecognizer(model, RATE)
recording = False
frame_queue = Queue()

print('stream started')

def recognize_speech():
    try:
        while True:
            data = frame_queue.get()
            if recognizer.AcceptWaveform(data):
                #speech_recog_start = time.time()
                result = json.loads(recognizer.Result())
                #speech_recog_end = time.time()
                #speech_recog_time = speech_recog_end - speech_recog_start
                text = result.get("text", "")
                print("Speech detected: " + text)
                #print("Time to recognise speech: ", speech_recog_time)
                server_data = {"type": "audio speech", "content": "Detected Speech: " + text}
                requests.post(url, json=server_data)
                #print(f"Response status code: {response.status_code}")
    except Exception as e:
        print(f"Error recognizing speech: {e}")

threshold = 1500  # Threshold for peak detection

recognizer_thread = threading.Thread(target=recognize_speech)
recognizer_thread.daemon = True
recognizer_thread.start()

try:
    while True:
       # start_time = time.time()
        data = stream.read(BUFFER, exception_on_overflow=False)
        data_int = struct.unpack(str(BUFFER) + 'h', data)
        filtered_data = sosfilt(sos, data_int)
        yf = fft(filtered_data)
        yf_magnitude = 2.0 / BUFFER * np.abs(yf[0:BUFFER // 2])
        peaks, properties = find_peaks(yf_magnitude, height=threshold)
        xf = fftfreq(BUFFER, (1 / RATE))[:BUFFER // 2]
        peak_frequencies = xf[peaks]
        peak_magnitudes = properties["peak_heights"]
            
        if len(peak_frequencies) > 0 and not recording:
            average_frequency = np.average(peak_frequencies)
            #average_magnitude = np.average(peak_magnitudes)
            #print("average magnitude: ", average_magnitude)
            #print("average frequency: ", average_frequency)
            frequency_buffer.append(average_frequency)
            if len(frequency_buffer) > moving_sample_size:
                frequency_buffer.pop(0)
            min_average_frequency = np.min(frequency_buffer)
            max_average_frequency = np.max(frequency_buffer)
            frequency_difference = max_average_frequency - min_average_frequency

            print(f"Frequency Difference: {frequency_difference} Hz \n")
            if frequency_difference > 100:
                server_data = {"type": "audio", "content": "Multiple Speakers Detected"}
                response = requests.post(url, json=server_data)
                # Print the response content
                print(response.status_code)  # Prints the status code (e.g., 200)
                print(response.json())       # Prints the JSON response content (if the response is JSON)
                recording_time = time.time()
                print("Recording")
                recording = True
                
        if recording:
            if time.time() - recording_time <= 5:
                frame_queue.put(data)
            else:
                recording = False
                #print("Ended")
        
        #end_time = time.time()
        #process_time = end_time - start_time

except KeyboardInterrupt:
    pass

stream.stop_stream()
stream.close()
audio.terminate()

print('stream stopped')
