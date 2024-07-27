import os

def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 1
set_affinity(0)

import time
from picamera2 import Picamera2
import libcamera
from multiprocessing import shared_memory
import cv2


def read_camera():
    print("read cam")
    shm_size = 800 * 600 * 3

    # Create a shared memory block
    shm = shared_memory.SharedMemory(name='shm_camera', create=True, size=shm_size)

    picam2 = Picamera2()

    picam2.configure(picam2.create_preview_configuration(main={"format": 'XRGB8888', "size": (800, 600)}, transform=libcamera.Transform(vflip=1)))

    #time.sleep(492) #wait for capture, gaze and face_recog to start

    picam2.start()
    time.sleep(2)

    try:
        while True:
            image = picam2.capture_array()
           
            if image.shape[2] == 4:  # RGBA format
                # Convert to RGB format (drop alpha channel)
                image = cv2.cvtColor(image, cv2.COLOR_RGBA2RGB)
                
           # Ensure both have the same structure
            if image.shape == (600, 800, 3):
                # Write the image data into shared memory
                shm.buf[:] = image.reshape(-1)
            else:
                print("Unexpected image shape:", image.shape)
            
            time.sleep(0.46)
    except KeyboardInterrupt:
        pass

    picam2.stop()
    shm.close()
    shm.unlink()
