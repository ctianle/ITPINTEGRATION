import time
import cv2
import mediapipe as mp
import numpy as np
import requests
import json
import os
from multiprocessing import shared_memory
import warnings
import sys

def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 1
set_affinity(1)


time.sleep(202) #wait face_recog to start

#flask server
calibration_url = "http://10.0.0.1/update_calibration"

logs_url = "http://10.0.0.1/store_data"

Position = 0


relative = lambda landmark, shape: (int(landmark.x * shape[1]), int(landmark.y * shape[0]))
relativeT = lambda landmark, shape: (int(landmark.x * shape[1]), int(landmark.y * shape[0]), 0)

# detect the face shape 
mp_face_mesh = mp.solutions.face_mesh
face_mesh = mp_face_mesh.FaceMesh(min_detection_confidence=0.5, min_tracking_confidence=0.5, refine_landmarks=True, max_num_faces = 2)

mp_drawing = mp.solutions.drawing_utils
drawing_spec = mp_drawing.DrawingSpec(thickness=1, circle_radius=1)
camera_vector = None
start_time = 0
timer_duration = 5

corners = []

# Define the size of the shared memory block (RGB image size)
shm_size = 800 * 600 * 3

# Open the existing shared memory block
shm = shared_memory.SharedMemory(name='shm_camera')

try:
    while True:
        image = np.ndarray(shape=(600, 800, 3), dtype=np.uint8, buffer=shm.buf)
        
        if image is None:
            continue
        
        image = cv2.cvtColor(cv2.flip(image, 1), cv2.COLOR_BGR2RGB)
        image.flags.writeable = False
        results = face_mesh.process(image)
        image.flags.writeable = True
        image = cv2.cvtColor(image, cv2.COLOR_RGB2BGR)
        if(start_time == 0):
            start_time = time.time()
            data = {"Status" : "Ready", "Position" : Position}
            response = requests.post(calibration_url, json=data)
        elapsed_time = time.time() - start_time

        img_h, img_w, img_c = image.shape

        if results.multi_face_landmarks:
            if(len(results.multi_face_landmarks) > 1):
                server_data = {"type": "Gaze", "content": "Multiple faces detected", "uuid": ""}
                response = requests.post(logs_url, json=server_data)
                print("Multiple faces detected")
            face_landmarks = results.multi_face_landmarks[0]

            image_points = np.array([
                relative(face_landmarks.landmark[4], image.shape),  # Nose tip
                relative(face_landmarks.landmark[152], image.shape),  # Chin
                relative(face_landmarks.landmark[263], image.shape),  # Left eye left corner
                relative(face_landmarks.landmark[33], image.shape),  # Right eye right corner
                relative(face_landmarks.landmark[287], image.shape),  # Left Mouth corner
                relative(face_landmarks.landmark[57], image.shape)  # Right mouth corner
            ], dtype="double")

            '''
            2D image points.
            relativeT takes mediapipe points that is normalized to [-1, 1] and returns image points
            at (x,y,0) format
            '''
            image_points1 = np.array([
                relativeT(face_landmarks.landmark[4], image.shape),  # Nose tip
                relativeT(face_landmarks.landmark[152], image.shape),  # Chin
                relativeT(face_landmarks.landmark[263], image.shape),  # Left eye, left corner
                relativeT(face_landmarks.landmark[33], image.shape),  # Right eye, right corner
                relativeT(face_landmarks.landmark[287], image.shape),  # Left Mouth corner
                relativeT(face_landmarks.landmark[57], image.shape)  # Right mouth corner
            ], dtype="double")

            model_points = np.array([
                (0.0, 0.0, 0.0),  # Nose tip
                (0, -63.6, -12.5),  # Chin
                (-43.3, 32.7, -26),  # Left eye, left corner
                (43.3, 32.7, -26),  # Right eye, right corner
                (-28.9, -28.9, -24.1),  # Left Mouth corner
                (28.9, -28.9, -24.1)  # Right mouth corner
            ])

            Eye_ball_center_right = np.array([[-29.05], [32.7], [-39.5]])
            Eye_ball_center_left = np.array([[29.05], [32.7], [-39.5]])

            focal_length = 1 * img_w

            camera_matrix = np.array([ [focal_length, 0, img_h / 2],
                                        [0, focal_length, img_w / 2],
                                        [0, 0, 1]])

            dist_coeffs = np.zeros((4, 1))  # Assuming no lens distortion
            success, rotation_vector, translation_vector = cv2.solvePnP(model_points, image_points, camera_matrix, dist_coeffs)

            left_pupil = relative(face_landmarks.landmark[468], image.shape)
            right_pupil = relative(face_landmarks.landmark[473], image.shape)
            _, transformation, _ = cv2.estimateAffine3D(image_points1, model_points)

            if transformation is not None:
                left_pupil_world_cord = transformation @ np.array([[left_pupil[0], left_pupil[1], 0, 1]]).T
                right_pupil_world_cord = transformation @ np.array([[right_pupil[0], right_pupil[1], 0, 1]]).T
               
                S_L = Eye_ball_center_left + (left_pupil_world_cord - Eye_ball_center_left) * 10
                S_R = Eye_ball_center_right + (right_pupil_world_cord - Eye_ball_center_right) * 10
                
                left_eye_pupil2D, _ = cv2.projectPoints(S_L.T, rotation_vector, translation_vector, camera_matrix, dist_coeffs)
                right_eye_pupil2D, _ = cv2.projectPoints(S_R.T, rotation_vector, translation_vector, camera_matrix, dist_coeffs)
            
                left_head_pose, _ = cv2.projectPoints((int(left_pupil_world_cord[0]), int(left_pupil_world_cord[1]), int(40)),
                                                rotation_vector, translation_vector, camera_matrix, dist_coeffs)
                
                right_head_pose, _ = cv2.projectPoints((int(right_pupil_world_cord[0]), int(right_pupil_world_cord[1]), int(40)),
                                                rotation_vector, translation_vector, camera_matrix, dist_coeffs)

                estimated_distance = int(S_L[2])

                left_gaze = left_pupil + (left_eye_pupil2D[0][0] - left_pupil) - (left_head_pose[0][0] - left_pupil)
                right_gaze = right_pupil + (right_eye_pupil2D[0][0] - right_pupil) - (right_head_pose[0][0] - right_pupil)
                
                mean_gaze = np.array([(left_gaze[0] + right_gaze[0]) * 0.5, (left_gaze[1] + right_gaze[1]) * 0.5])
                

                countdown = timer_duration - elapsed_time
                if(camera_vector is None and countdown <= 0):
                    camera_vector = np.array([mean_gaze[0], mean_gaze[1]])
                    Position += 1
                    data = {"Status" : "Ready", "Position" : Position}
                    response = requests.post(calibration_url, json=data)
                    print("set middle:")
                    start_time = time.time()  # Reset start_time to current time
                    countdown = timer_duration

                if(camera_vector is not None):
                    gaze_vector = np.array([int(mean_gaze[0]) - int(left_pupil[0]), int(mean_gaze[1]) - int(left_pupil[1]), estimated_distance])
                    correct_gaze = np.array([int(camera_vector[0]) - int(left_pupil[0]), int(camera_vector[1]) - int(left_pupil[1]), estimated_distance])

                    vector1_normalized = gaze_vector / np.linalg.norm(gaze_vector)
                    vector2_normalized = correct_gaze / np.linalg.norm(correct_gaze)
                    # Compute the dot product
                    dot_product = np.dot(vector1_normalized, vector2_normalized)
                    # Calculate the angle in degrees
                    angle = np.arccos(dot_product) * 180 / np.pi
                       
                    if(len(corners) < 4 and countdown <= 0):
                        corners.append(angle)
                        Position += 1
                        data = {"Status" : "Ready", "Position" : Position}
                        response = requests.post(calibration_url, json=data)
                        print("angle added:", angle)
                        if(len(corners) == 4):
                            angle_gap = max(corners) - min(corners)
                            print("difference", angle_gap)
                            if(angle_gap > 15 or max(corners) < 10):
                                #reset
                                Position = 0
                                print("Recalibrate")
                                data = {"Status" : "Ready", "Position" : Position}
                                response = requests.post(calibration_url, json=data)
                                #print(response.text)
                                corners.clear() 
                                camera_vector = None
                            else:
                                average_angle = sum(corners) / len(corners)
                                print("average:", average_angle)
                                Position += 1
                                data = {"Status" : "Ready", "Position" : Position}
                                response = requests.post(calibration_url, json=data)
                                print(response.text)
                        start_time = time.time()
                        countdown = timer_duration

                    elif(len(corners) ==  4):
                        if(angle > average_angle):
                            difference = angle - average_angle
                            if(difference < 10):
                               server_data = {"type": "Gaze", "content": "User looking away from screen. Code : green", "uuid": ""}
                               response = requests.post(logs_url, json=server_data)
                               print("green")
                            elif(difference >= 10 and  difference < 20):
                                server_data = {"type": "Gaze", "content": "User looking away from screen. Code : yellow", "uuid": ""}
                                response = requests.post(logs_url, json=server_data)
                                print("yellow")
                            else:
                                server_data = {"type": "gaze", "content": "User looking away from screen. Code : red", "uuid": ""}
                                response = requests.post(logs_url, json=server_data)
                                print("red")


                    #p1 = (int(left_pupil[0]), int(left_pupil[1]))
                    #p2 = (int(mean_gaze[0]), int(mean_gaze[1]))

                    #cv2.line(image, p1, p2, (0, 255, 0), 2)
        
        else:
            server_data = {"type": "Gaze", "content": "No faces detected", "uuid": ""}
            response = requests.post(logs_url, json=server_data)
            #print("no faces detected")
        
        #cv2.imshow('Head Pose Estimation', image)
        
        time.sleep(0.18)
        

except KeyboardInterrupt:
    pass
        
shm.close()
cv2.destroyAllWindows()
