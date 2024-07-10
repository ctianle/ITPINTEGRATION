import cv2
import time
import os
from datetime import datetime
import subprocess


def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 1
set_affinity(1)

# Directory to save images
save_dir = 'captures'
if not os.path.exists(save_dir):
    os.makedirs(save_dir)

cap = cv2.VideoCapture(2)

try:
    while cap.isOpened():
        success, image = cap.read()
        
        if not success:
            break

        key = cv2.waitKey(5)
        # Flip the image horizontally for a later selfie-view display
        # Also convert the color space from BGR to RGB
        image = cv2.cvtColor(cv2.flip(image,1), cv2.COLOR_BGR2RGB)
        
        # To improve performance     
        image.flags.writeable = True
        
        # Convert the color space from BGR to RGB
        image = cv2.cvtColor(image, cv2.COLOR_RGB2BGR)
            
        # Display the image
        #cv2.imshow('Head Pose Estimation', image)
        
        # Save the image to the specified directory using timestamp
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S_%f')
        image_filename = os.path.join(save_dir, f'image_{timestamp}.jpg')
        cv2.imwrite(image_filename, image)
        print("Image captured")
        
        #if key & 0xFF == 27:
        #    break
        time.sleep(2)
        
except KeyboardInterrupt:
    pass
    
cap.release()
cv2.destroyAllWindows()
