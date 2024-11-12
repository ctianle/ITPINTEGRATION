import os
import time
import cv2
from mediapipe import solutions
import numpy as np
import requests
import json
from multiprocessing import shared_memory
import base64

  
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


def gaze_detect():
    calibration_url = "http://10.0.0.1/update_calibration"
    resolution_url = "http://10.0.0.1/get_resolution"
    logs_url = "http://10.0.0.1/store_data"
    
    screenshots_directory = "screenshots"
    debug_directory = "debug"

    Position = 0
    calculated = False

    relative = lambda landmark, shape: (int(landmark.x * shape[1]), int(landmark.y * shape[0]))
    relativeT = lambda landmark, shape: (int(landmark.x * shape[1]), int(landmark.y * shape[0]), 0)

    # detect the face shape 
    mp_face_mesh = solutions.face_mesh
    face_mesh = mp_face_mesh.FaceMesh(min_detection_confidence=0.5, min_tracking_confidence=0.5, refine_landmarks=True, max_num_faces = 2)

    start_time = 0
    timer_duration = 5
    
    reset_counter = 0
    
    x_corners = []
    y_corners = []
    
    screen_width = 0
    screen_height = 0


    # Define the size of the shared memory block (RGB image size)
    shm_size = 1640 * 1232 * 3

    # Open the existing shared memory block
    shm = shared_memory.SharedMemory(name='shm_camera')

    first_captured = False
    save_dir = 'captures'
    if not os.path.exists(save_dir):
        os.makedirs(save_dir)
    
    #clear previouss session screenshots
    if os.path.isdir(screenshots_directory) and os.listdir(screenshots_directory):
        for filename in os.listdir(screenshots_directory):
            file_path = os.path.join(screenshots_directory, filename)
            # Check if it's a file (not a subdirectory) and delete it
            if os.path.isfile(file_path):
                os.remove(file_path)
            

    try:
        while True:
            #print("gaze")
            #performance_start = time.time()
            image = np.ndarray(shape=(1232, 1640, 3), dtype=np.uint8, buffer=shm.buf)
            
            if image is None:
                print("image is none")
                continue
            
            image = cv2.cvtColor(cv2.flip(image, 1), cv2.COLOR_BGR2RGB)
            image.flags.writeable = False
            results = face_mesh.process(image)
            image.flags.writeable = True
            image = cv2.cvtColor(image, cv2.COLOR_RGB2BGR)
            if(start_time == 0):
                start_time = time.time()
                data = {"Status" : "Ready", "Position" : Position}
                requests.post(calibration_url, json=data)
            elapsed_time = time.time() - start_time

            img_h, img_w, img_c = image.shape

            if results.multi_face_landmarks:
                if(len(results.multi_face_landmarks) > 1):
                    server_data = {"type": "Gaze", "content": "Multiple faces detected"}
                    requests.post(logs_url, json=server_data)
                
                if not first_captured:
                    first_image_filename = os.path.join(save_dir, f'first_image.png')
                    cv2.imwrite(first_image_filename, image)
                    first_captured = True
                
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
                   
                    S_L = Eye_ball_center_left + (left_pupil_world_cord - Eye_ball_center_left) * 20
                    S_R = Eye_ball_center_right + (right_pupil_world_cord - Eye_ball_center_right) * 20
                    
                    left_eye_pupil2D, _ = cv2.projectPoints(S_L.T, rotation_vector, translation_vector, camera_matrix, dist_coeffs)
                    right_eye_pupil2D, _ = cv2.projectPoints(S_R.T, rotation_vector, translation_vector, camera_matrix, dist_coeffs)
                
                    left_head_pose, _ = cv2.projectPoints((int(left_pupil_world_cord[0]), int(left_pupil_world_cord[1]), int(40)),
                                                    rotation_vector, translation_vector, camera_matrix, dist_coeffs)
                    
                    right_head_pose, _ = cv2.projectPoints((int(right_pupil_world_cord[0]), int(right_pupil_world_cord[1]), int(40)),
                                                    rotation_vector, translation_vector, camera_matrix, dist_coeffs)


                    left_gaze = left_pupil + (left_eye_pupil2D[0][0] - left_pupil) - (left_head_pose[0][0] - left_pupil)
                    right_gaze = right_pupil + (right_eye_pupil2D[0][0] - right_pupil) - (right_head_pose[0][0] - right_pupil)
                    
                    mean_gaze = np.array([(left_gaze[0] + right_gaze[0]) * 0.5, (left_gaze[1] + right_gaze[1]) * 0.5])
                    
                    if not calculated:
                        countdown = timer_duration - elapsed_time
                        if(len(x_corners) < 4 and countdown <=0):
                            if(len(x_corners) == 0):
                                    x_corners.append(mean_gaze[0])
                                    y_corners.append(mean_gaze[1])
                                    print("First Corner Added", mean_gaze)
                                    Position += 1
                                    data = {"Status" : "Ready", "Position" : Position}
                                    requests.post(calibration_url, json=data)
                            elif(len(x_corners) == 1 and abs(mean_gaze[1] - y_corners[-1]) < 100):
                                x_corners.append(mean_gaze[0])
                                y_corners.append(mean_gaze[1])
                                print("Second Corner Added", mean_gaze)
                                Position += 1
                                data = {"Status" : "Ready", "Position" : Position}
                                requests.post(calibration_url, json=data)
                            elif(len(x_corners) == 2 and abs(mean_gaze[0] - x_corners[-1]) < 100):
                                x_corners.append(mean_gaze[0])
                                y_corners.append(mean_gaze[1])
                                print("Third Corner Added", mean_gaze)
                                Position += 1
                                data = {"Status" : "Ready", "Position" : Position}
                                requests.post(calibration_url, json=data)
                            elif(len(x_corners) == 3 and abs(mean_gaze[1] - y_corners[-1]) < 100): 
                                if(abs(x_corners[0] - mean_gaze[0]) < 100):
                                    x_corners.append(mean_gaze[0])
                                    y_corners.append(mean_gaze[1])
                                    Position += 1
                                    data = {"Status" : "Ready", "Position" : Position}
                                    requests.post(calibration_url, json=data)
                                else:
                                    print("Last point does not match first point", mean_gaze)
                                    reset_counter += 1
                            else:
                                print("Difference too much. Try again or reset", mean_gaze)
                                reset_counter += 1
                            
                            if(reset_counter == 5):
                                print("Recalibrate")
                                reset_counter = 0
                                x_corners.clear()
                                y_corners.clear()
                                Position = 0
                                data = {"Status" : "Ready", "Position" : Position}
                                requests.post(calibration_url, json=data)
                                
                            
                            start_time = time.time()
                            countdown = timer_duration
                            
                        elif (len(x_corners) == 4):
                            #min_x = (x_corners[0] + x_corners[3]) * 0.5
                            #max_x = (x_corners[1] + x_corners[2]) * 0.5
                            
                            #flipped corners due to vflip 
                            min_x = (x_corners[1] + x_corners[2]) * 0.5
                            max_x = (x_corners[0] + x_corners[3]) * 0.5

                            min_y= (y_corners[0] + y_corners[1]) * 0.5
                            max_y = (y_corners[2] + y_corners[3]) * 0.5
                            
                            print("min x:", min_x)
                            print("max x:", max_x)

                            print("min y:", min_y)
                            print("max y:", max_y)
                            
                            p1 = (int(min_x), int(min_y))
                            p2 = (int(max_x), int(min_y))
                            p3 = (int(max_x), int(max_y))
                            p4 = (int(min_x), int(max_y))
                            
                            cv2.line(image, p1, p2, (0, 0, 255), 2)
                            cv2.line(image, p2, p3, (0, 0, 255), 2)
                            cv2.line(image, p3, p4, (0, 0, 255), 2)
                            cv2.line(image, p4, p1, (0, 0, 255), 2)
                            calibrated_image_filename = os.path.join(save_dir, f'calibrated.png')
                            cv2.imwrite(calibrated_image_filename, image)
                            calculated = True
                    
                    else:    
                        if (screen_width== 0 and screen_height == 0):
                            response = requests.get(resolution_url)
                            if response.status_code == 200:
                                data = response.json()
                                screen_width = data.get("Width")
                                screen_height = data.get("Height")
                                print(screen_width, screen_height)

                        else:
                            calibrated_x = screen_width - (((mean_gaze[0] - min_x) / (max_x - min_x)) * screen_width)
                            calibrated_y = ((mean_gaze[1] - min_y) / (max_y - min_y)) * screen_height
                            if os.path.isdir(screenshots_directory) and os.listdir(screenshots_directory):
                                # Get the first image file in the directory (you can modify this to load specific images)
                                screenshot_file = os.listdir(screenshots_directory)[0]
                                screenshot_path = os.path.join(screenshots_directory, screenshot_file)
                                
                                # Load the image using OpenCV
                                screenshot_image = cv2.imread(screenshot_path)
                                if screenshot_image is not None:
                                    # Draw a circle on the image
                                    # Parameters: image, center coordinates, radius, color (BGR), thickness
                                    center_coordinates = (int(calibrated_x), int(calibrated_y))
                                    radius = 50
                                    color = (0, 255, 0)  # Green color in BGR
                                    thickness = 2  # Thickness of the circle
                                    # Draw the circle
                                    cv2.circle(screenshot_image, center_coordinates, radius, color, thickness)
                                    output_filename = os.path.join(debug_directory, 'output_image_with_circle.jpg')
                                    cv2.imwrite(output_filename, screenshot_image)
                                    compressed_image_bytes = compress_image(screenshot_image)
                                    image_base64 = base64.b64encode(compressed_image_bytes).decode('utf-8')
                                    server_data = {"type": "gaze coordinates", "content": image_base64}
                                    requests.post(logs_url, json=server_data)
                                    os.remove(screenshot_path)
                                    

                        if(mean_gaze[0] < min_x or mean_gaze[1] < min_y or mean_gaze[0] > max_x or mean_gaze[1] > max_y):    
                            if(mean_gaze[0] < min_x or mean_gaze[1] < min_y):
                                distance = max(abs(mean_gaze[0] - min_x), 
                                                   abs(mean_gaze[1] - min_y))
                                                   
                            elif(mean_gaze[0] > max_x or mean_gaze[1] > max_y):
                                distance = max(abs(mean_gaze[0] - max_x), 
                                                  abs(mean_gaze[1] - max_y))
        
                            if(distance < 200):
                                server_data = {"type": "Gaze", "content": "User looking away from screen. Code : green"}
                                requests.post(logs_url, json=server_data)
                                print("green")
                            elif(distance < 300):
                                server_data = {"type": "Gaze", "content": "User looking away from screen. Code : yellow"}
                                requests.post(logs_url, json=server_data)
                                print("yellow")
                            else:
                                server_data = {"type": "Gaze", "content": "User looking away from screen. Code : red"}
                                requests.post(logs_url, json=server_data)
                                print("red")
            
            else:
                server_data = {"type": "Gaze", "content": "No faces detected"}
                requests.post(logs_url, json=server_data)
                print("no faces detected")
            
            #performance_end = time.time()
            #diff = performance_end - performance_start
            #print("gaze process time", diff)
            time.sleep(0.18)
            
    
    except KeyboardInterrupt:
        pass
     
    shm.close()
    cv2.destroyAllWindows()

