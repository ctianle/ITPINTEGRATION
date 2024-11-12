import os
from deepface.DeepFace import verify, build_model
import requests
import json
import time

model = build_model('DeepID')

def verify_face():
    # Path to the captures folder
    #print("recog start")
    first_image_path = "captures/first_image.png"
    last_image_path = "captures/last_image.png"

    url = "http://10.0.0.1/store_data"

    try:
        while True:
            #print("recog")
            if not os.path.exists(first_image_path) or not os.path.exists(last_image_path):
                print("Image files do not exist")
                
            else:
                #print("Start comparing")

                try:
                    # Compare the two images using the DeepFace model with enforce_detection=True
                    result = verify(img1_path=first_image_path, img2_path=last_image_path, model_name='DeepID', align=True, enforce_detection=True)

                    # Print the similarity score and verification result
                    #print(f"Results: {result}")
                    if result['verified']:
                        print("Faces match!")
                    else:
                        server_data = {"type": "face verification", "content": "Different face detected."}
                        response = requests.post(url, json=server_data)
                        print("Faces do not match.")
                    
                    #os.remove(last_image_path)
                except ValueError as e:
                    server_data = {"type": "face verification", "content": "No face found in one image."}
                    response = requests.post(url, json=server_data)
                    os.remove(last_image_path)
                    print(f"No face found in one or both images: {e}")
           
            time.sleep(0.38)
            
    except KeyboardInterrupt:
        pass

