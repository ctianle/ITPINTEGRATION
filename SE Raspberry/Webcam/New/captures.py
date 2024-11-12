import os
import time
import cv2
from multiprocessing import shared_memory
import numpy as np
import requests
import base64


#time.sleep(482) #wait for gaze and face_recog to start

def compress_image(image, scale_percent=50, quality=50):
    # Resize the image
    width = int(image.shape[1] * scale_percent / 100)
    height = int(image.shape[0] * scale_percent / 100)
    dim = (width, height)
    resized_image = cv2.resize(image, dim, interpolation=cv2.INTER_AREA)
    
    # Compress the image
    encode_param = [int(cv2.IMWRITE_JPEG_QUALITY), quality]
    result, encimg = cv2.imencode('.jpg', resized_image, encode_param)
    
    if result:
        return encimg.tobytes()
    else:
        raise ValueError("Failed to compress the image")

def capture_images():
    print("start capture")
    url = "http://10.0.0.1/store_data"

    # Directory to save images for comparison
    save_dir = 'captures'
    if not os.path.exists(save_dir):
        os.makedirs(save_dir)

    # Define the size of the shared memory block (RGB image size)
    shm_size = 1640 * 1232 * 3

    # Open the existing shared memory block
    shm = shared_memory.SharedMemory(name='shm_camera')


    try:
        while True:
            #print("capture")
            #performance_start = time.time()
            image_data = np.ndarray(shape=(1232, 1640, 3), dtype=np.uint8, buffer=shm.buf)
            
            compressed_image_bytes = compress_image(image_data)
            image_base64 = base64.b64encode(compressed_image_bytes).decode('utf-8')
            server_data = {"type": "camera image", "content": image_base64}
            requests.post(url, json=server_data)
            #print(f"Response: {response.json()}")
            
            last_image_filename = os.path.join(save_dir, f'last_image.png')
            if not os.path.exists(last_image_filename):
                cv2.imwrite(last_image_filename, image_data)
            
            #performance_end = time.time()
            #diff = performance_end - performance_start
            #print("captures process time", diff)
            time.sleep(0.98)
            
    except KeyboardInterrupt:
        pass
        
    shm.close()
    cv2.destroyAllWindows()
