#%% Import the required libraries
import pyaudio # Refer to https://people.csail.mit.edu/hubert/pyaudio/
import struct  # Refer to https://docs.python.org/3/library/struct.html (Used for converting audio read as bytes to int16)
import numpy as np 
import matplotlib.pyplot as plt
from scipy.fftpack import fft, fftfreq # Refer to https://docs.scipy.org/doc/scipy/tutorial/fft.html (Used for Fourier Spectrum to display audio frequencies)
import time # In case time of execution is required
from scipy.signal import butter, sosfilt, find_peaks
import threading  # Import threading module for managing concurrent tasks
import speech_recognition as sr
import os
import sounddevice
import requests
import json

url = "http://10.0.0.1/store_data"

server_data = {
    "type" : "Audio",
    "content" : "Multiple speakers detected"
}

def list_audio_devices():
    p = pyaudio.PyAudio()
    info = p.get_host_api_info_by_index(0)
    num_devices = info.get('deviceCount')
    for i in range(num_devices):
        device_info = p.get_device_info_by_host_api_device_index(0, i)
        if device_info.get('maxInputChannels') > 0:
            print(f"Input Device id {i} - {device_info.get('name')}")
        if device_info.get('maxOutputChannels') > 0:
            print(f"Output Device id {i} - {device_info.get('name')}")
    p.terminate()

list_audio_devices()

def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 2
set_affinity(2)

#%% Parameters
BUFFER = 2048   # samples per frame (you can change the same to acquire more or less samples)
FORMAT = pyaudio.paInt16     # audio format (bytes per sample)
CHANNELS = 1                 # single channel for microphone
RATE = 16000                 # samples per second
RECORD_SECONDS = 20          # Specify the time to record from the microphone in seconds

def design_filter(lowfreq, highfreq, fs, order=3):
    nyq = 0.5*fs
    low = lowfreq/nyq
    high = highfreq/nyq
    sos = butter(order, [low,high], btype='band',output='sos')
    return sos

# design the filter
sos = design_filter(50, 300, RATE, 3) #change the lower and higher frequencies according to choice

#%% create matplotlib figure and axes with initial random plots as placeholder
#fig, (ax1, ax2) = plt.subplots(2, figsize=(7, 7))
# create a line object with random data
#x = np.arange(0, 2*BUFFER, 2)       # samples (waveform)
xf = fftfreq(BUFFER, (1/RATE))[:BUFFER//2]

#line, = ax1.plot(x,np.random.rand(BUFFER), '-', lw=2)
#line_fft, = ax2.plot(xf,np.random.rand(BUFFER//2), '-', lw=2)

# basic formatting for the axes
#ax1.set_title('AUDIO WAVEFORM')
#ax1.set_xlabel('samples')
#ax1.set_ylabel('volume')
#ax1.set_ylim(-5000, 5000) # change this to see more amplitude values (when we speak)
#ax1.set_xlim(0, BUFFER)

#ax2.set_title('SPECTRUM')
#ax2.set_xlabel('Frequency')
#ax2.set_ylabel('Log Magnitude')
#ax2.set_ylim(0, 3000) 
#ax2.set_xlim(0, RATE/2)

# Do not show the plot yet
#plt.show(block=False)

#%% Initialize the pyaudio class instance
#audio = pyaudio.PyAudio()

# stream object to get data from microphone

def open_stream():
    audio = pyaudio.PyAudio()
    stream = audio.open(
            rate=RATE,
            format=FORMAT,
            channels=CHANNELS,
            input=True,
            input_device_index=0)
            
    return audio, stream
    
audio, stream = open_stream()

exec_time = []
frequency_buffer = []
moving_sample_size = 2
r = sr.Recognizer() #Initializing the Recognizer class
mic = sr.Microphone(device_index=0)
stream_open = True 

print('stream started')
        
def recognize_speech(speech):
    try:
        print("Speech detected: " + r.recognize_google(speech))
    except sr.UnknownValueError:
        print("Google Speech Recognition could not understand audio")

threshold = 1000  # Threshold for peak detection
#for _ in range(0, RATE // BUFFER * RECORD_SECONDS):   
try:
    while True:
        performance_start = time.time()
        # binary data
        data = stream.read(BUFFER, exception_on_overflow=False)  
       
        # convert data to 16bit integers
        data_int = struct.unpack(str(BUFFER) + 'h', data)    
        
        # compute FFT    
        start_time=time.time()  # for measuring frame rate
        filtered_data = sosfilt(sos, data_int)
        yf = fft(filtered_data)

        yf_magnitude = 2.0/BUFFER * np.abs(yf[0:BUFFER//2])

        # calculate time of execution of FFT
        exec_time.append(time.time() - start_time)
        
         # Identify peaks
        peaks, _ = find_peaks(yf_magnitude, height=threshold)
        
       # Get the frequencies corresponding to the peaks
        peak_frequencies = xf[peaks]

        # Check if there are peaks
        if len(peak_frequencies) > 0:
            average_frequency = np.average(peak_frequencies)
            
            frequency_buffer.append(average_frequency)

            if(len(frequency_buffer) > moving_sample_size):
                frequency_buffer.pop(0)

            
            min_average_frequency = np.min(frequency_buffer)
            max_average_frequency = np.max(frequency_buffer)

            # Compute the frequency difference
            frequency_difference = max_average_frequency - min_average_frequency

            print(f"Frequency Difference: {frequency_difference} Hz")
            if(frequency_difference > 100):
                response = requests.post(url, json=server_data)
                print(f"Response status code: {response.status_code}")
                print(f"Response JSON: {response.json()}")
                if(stream_open):
                    stream.close()
                    audio.terminate()
                    stream_open = False
                with mic as source:
                    r.adjust_for_ambient_noise(source) #Important step to identify the ambient noise and hence be silent during this phase
                    print("Recording")
                    speech = r.listen(source, phrase_time_limit=5) # Listening from microphone
                threading.Thread(target=recognize_speech, args=(speech, )).start()
                if(not stream_open):
                    frequency_buffer.clear()
                    audio, stream = open_stream()
                    stream_open = True
        
        performance_end = time.time()
        process_time = performance_end - performance_start
        #print("process_time: ",process_time)
        #update line plots for both axes
        #line.set_ydata(data_int)
        #line_fft.set_ydata(yf_magnitude)
        #fig.canvas.draw()
        #fig.canvas.flush_events()

except KeyboardInterrupt:
    pass

if(stream_open):
    stream.close()
    audio.terminate()
   
print('stream stopped')
print('average execution time = {:.0f} milli seconds'.format(np.mean(exec_time)*1000))  
