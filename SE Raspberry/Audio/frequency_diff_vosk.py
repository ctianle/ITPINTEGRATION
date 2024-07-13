import pyaudio
import struct
import numpy as np
from scipy.fftpack import fft, fftfreq
from scipy.signal import butter, sosfilt, find_peaks
import threading
import requests
import time
import os
import vosk
import json
import sounddevice
from queue import Queue

url = "http://10.0.0.1/store_data"

server_data = {
    "type": "Audio",
    "content": "Multiple speakers detected"
}

def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 2
set_affinity(2)

# Parameters
BUFFER = 2048  # samples per frame
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
    
exec_time = []
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
                result = json.loads(recognizer.Result())
                text = result.get("text", "")
                print("Speech detected: " + text)
                server_data = {"type": "Audio", "content": "Detected Speech: " + text, "uuid": ""}
                response = requests.post(url, json=server_data)
                print(f"Response status code: {response.status_code}")
                print(f"Response JSON: {response.json()}")
    except Exception as e:
        print(f"Error recognizing speech: {e}")

threshold = 1500  # Threshold for peak detection

recognizer_thread = threading.Thread(target=recognize_speech)
recognizer_thread.daemon = True
recognizer_thread.start()

try:
    while True:
        performance_start = time.time()
        data = stream.read(BUFFER, exception_on_overflow=False)
        data_int = struct.unpack(str(BUFFER) + 'h', data)
        start_time = time.time()
        filtered_data = sosfilt(sos, data_int)
        yf = fft(filtered_data)
        yf_magnitude = 2.0 / BUFFER * np.abs(yf[0:BUFFER // 2])
        exec_time.append(time.time() - start_time)
        peaks, _ = find_peaks(yf_magnitude, height=threshold)
        xf = fftfreq(BUFFER, (1 / RATE))[:BUFFER // 2]
        peak_frequencies = xf[peaks]
        
        if recording:
            if time.time() - recording_time <= 5:
                frame_queue.put(data)
            else:
                recording = False
                print("Ended")
        else:
            if len(peak_frequencies) > 0:
                average_frequency = np.average(peak_frequencies)
                frequency_buffer.append(average_frequency)
                if len(frequency_buffer) > moving_sample_size:
                    frequency_buffer.pop(0)
                min_average_frequency = np.min(frequency_buffer)
                max_average_frequency = np.max(frequency_buffer)
                frequency_difference = max_average_frequency - min_average_frequency

                print(f"Frequency Difference: {frequency_difference} Hz")
                if frequency_difference > 100:
                    recording_time = time.time()
                    print("Recording")
                    recording = True

        performance_end = time.time()
        process_time = performance_end - performance_start
except KeyboardInterrupt:
    pass

stream.stop_stream()
stream.close()
audio.terminate()

print('stream stopped')
print('average execution time = {:.0f} milli seconds'.format(np.mean(exec_time) * 1000))
