import os
import time
from picamera2 import Picamera2
import libcamera
from multiprocessing import shared_memory
import cv2


def read_camera():
    #print("read cam")
    shm_size = 1640 * 1232 * 3

    # Create a shared memory block
    shm = shared_memory.SharedMemory(name='shm_camera', create=True, size=shm_size)

    picam2 = Picamera2()

    picam2.configure(picam2.create_preview_configuration(main={"format": 'RGB888', "size": (1640, 1232)}, transform=libcamera.Transform(vflip=1)))

    #time.sleep(492) #wait for capture, gaze and face_recog to start
    picam2.start()
    time.sleep(2)
    
    #frame_count = 0
    #start_time = time.time()
    
    try:
        while True:
            #performance_start = time.time()
            #print("read")
            image = picam2.capture_array()
                
           # Ensure both have the same structure
            if image.shape == (1232, 1640, 3):
                # Write the image data into shared memory
                shm.buf[:] = image.reshape(-1)
            else:
                print("Unexpected image shape:", image.shape)
            
            
             # Increment frame count
            #frame_count += 1

            # Calculate elapsed time
            #elapsed_time = time.time() - start_time

            # Print the framerate every second
            #if elapsed_time >= 1.0:
            #    print(f"Camera Read Framerate: {frame_count / elapsed_time:.2f} FPS")
                # Reset for the next calculation
            #    frame_count = 0
            #    start_time = time.time()
            
            #performance_end = time.time()
            #diff = performance_end - performance_start
            #print("read camera process time", diff)
            time.sleep(1.5)
    except KeyboardInterrupt:
        pass

    picam2.stop()
    shm.close()
    shm.unlink()
