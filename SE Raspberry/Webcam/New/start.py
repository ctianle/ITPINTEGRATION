import os
def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

set_affinity(0)
    
import time
import cv2
import numpy as np
import requests
import json
from multiprocessing import shared_memory
from mediapipe import solutions
from deepface.DeepFace import verify
from picamera2 import Picamera2
import libcamera
import base64
import threading

from read_camera import read_camera
from captures import capture_images
from face_detect_eyes import gaze_detect
from face_recog import verify_face


    
def thread_function(func):
    thread = threading.Thread(target=func)
    thread.start()
    return thread

if __name__ == "__main__":
    
    # Start functions in separate threads
    threads = []
    threads.append(thread_function(read_camera))
    time.sleep(4)  # Adjust as necessary

    threads.append(thread_function(capture_images))
    time.sleep(4)  # Adjust as necessary

    threads.append(thread_function(gaze_detect))
    time.sleep(4)  # Adjust as necessary

    threads.append(thread_function(verify_face))
