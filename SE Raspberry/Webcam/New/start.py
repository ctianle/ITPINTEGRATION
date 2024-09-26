import os
def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

set_affinity(1)
    
import time
import threading
from read_camera import read_camera
from captures import capture_images
from face_detect_eyes import gaze_detect
from face_recog import verify_face

def create_thread(func):
    return threading.Thread(target=func)

if __name__ == "__main__":
    
    # Start functions in separate threads
    threads = []
    threads.append(create_thread(read_camera))
    threads.append(create_thread(capture_images))
    threads.append(create_thread(gaze_detect))
    threads.append(create_thread(verify_face))
    
    print("start")
    threads[3].start()
    threads[0].start()
    time.sleep(2)
    threads[1].start()
    threads[2].start()
