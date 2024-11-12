import os
def set_affinity(core_id):
    command = f"taskset -cp {core_id} {os.getpid()}"
    os.system(command)

# Set the current process to run on core 3
set_affinity(3)

from flask import Flask, jsonify, request
from functions import *
from datetime import datetime
import base64

app = Flask(__name__)

# Directory to store the temporary files
TEMP_STORAGE = '/tmp/proctoring_data'

# Directory to detect gaze coords
WEBCAM_FOLDER = '/home/raspberry/Desktop/Webcam/screenshots'

if not os.path.exists(TEMP_STORAGE):
    os.makedirs(TEMP_STORAGE)

#create folder before hand and st permissions
UPLOAD_FOLDER = '/home/raspberry/flaskserver/images'

HTTP_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH', 'BREW']

PuK = False
fernet_key = None
JwT_token = None

calibration_ready = False
position = 0
calibration_position = 0
screen_width = 0
screen_height = 0

@app.route("/", methods = HTTP_METHODS)
def index():
    global PuK
    global JwT_token
    """POST - Receive data that was captured by the proctoring script

    Keyword arguments:
    (from the host)
    AWD = Active Windows Detection (string), the application window that was active at the time of recording
    AMD = Active Monitor Detection (string), the number of monitors currently active
    PL = Process List (string), the list of processes currently running
    OW = Windows that are opened
    PuK = Public Key of the Invigilator's portal
    (example)
        "AWD" : "Telegram",
        "AMD" : "3",
        "PL" : "[Telegram.exe, google, svchost, ...]"
        "PuK" : XXXXXXXXXXXX
    Return: encrypted and encoded data in JSON
    """
    if request.method == 'POST':
        data = request.get_json()
        category = ''
        decoded = ''
        process_list = [] # we want the list to reset to empty everytime, if not the result will stack
        """
        Have to check for key to know the different proctoring feature's data that had been sent
        Since we will be sending different proctoring results at different timings
        Data is sent in base64 encoding
        """
        if data:
            # check if incoming data is the Public Key
            if "PuK" in data:
                # process the public key
                store_public_key(data["PuK"])
                PuK = True
                return jsonify(constructMacResponse())

            # check if there is a Public Key stored before processing the data
            elif PuK:
                if 'Token' in data:
                    print("ASKING FOR TOKEN")
                    return jsonify(constructPingResponse(JwT_token, gen_key()))

                if 'AWD' in data:
                    category = 'AWD'
                    # decoding data
                    # decode the data from base64 and convert to readable text
                    decoded = decodebase64(data[category])

                    # processing data
                    processing(decoded, category)
                    print("Returning AWD")
                    if JwT_token == None:
                        return ('No Token', 404)
                    return jsonify(constructDataResponse(decoded, category, gen_key(), JwT_token))

                if 'AMD' in data:
                    category = 'AMD'
                    # decoding data
                    # decode the data from base64 and convert to readable text
                    decoded = decodebase64(data[category])

                    # processing data
                    processing(decoded, category)
                    print("Returning AMD")
                    if JwT_token == None:
                        return ('No Token', 404)
                    return jsonify(constructDataResponse(decoded, category, gen_key(), JwT_token))

                if 'PL' in data:
                    category = 'PL'
                    # decoding each item in the list of data
                    for item in data[category]:
                        # decode the data from base64 and convert to readable text
                        decoded = decodebase64(item)
                        process_list.append(decoded)

                    # processing data
                    processing(process_list, category)
                    print("Returning PL")
                    if JwT_token == None:
                        return ('No Token', 404)
                    return jsonify(constructDataResponse(process_list, category, gen_key(), JwT_token))

                if 'OW' in data:
                    category = 'OW'
                    # decoding each item in the list of data
                    for item in data[category]:
                        # decode the data from base64 and convert to readable text
                        decoded = decodebase64(item)
                        process_list.append(decoded)

                    # processing data
                    processing(process_list, category)
                    print("Returning OW")
                    if JwT_token == None:
                        return ('No Token', 404)
                    return jsonify(constructDataResponse(process_list, category, gen_key(), JwT_token))

                if 'KS' in data:
                    category = 'KS'
                    # decoding data
                    # decode the data from base64 and convert to readable text
                    decoded = decodebase64(data[category])
                    print("Returning KS")
                    if JwT_token == None:
                        return ('No Token', 404)
                    return jsonify(constructDataResponse(decoded, category, gen_key(), JwT_token))
                
                if 'VM' in data:
                    category = 'VM'
                    # decoding data
                    # decode the data from base64 and convert to readable text
                    decoded = decodebase64(data[category])
                    print("Returning VM")
                    if JwT_token == None:
                        return ('No Token', 404)
                    return jsonify(constructDataResponse(decoded, category, gen_key(), JwT_token))

            else:
                # return 404, not found if there is no public key and data is received
                return('Public key not found', 404)

    # if any other methods were used but not allowed, return 200, success
    else:
        return ('', 200)

# Check student ID already exists
@app.route('/check_studentid', methods=['GET'])
def check_studentid_endpoint():
    exists = studentid_exists()
    return jsonify({"exists": exists})

# Returns Encrypted CSR + Encrypted Fernet Key
@app.route('/get_csr', methods=['GET'])
def get_csr_endpoint():
    studentid = request.args.get('studentid')
    if not studentid:
        return jsonify({"status": "error", "message": "Student ID not provided"}), 400
    encrypted_csr_data, encrypted_sessionkey = get_csr(studentid)
    return jsonify({"csr": encrypted_csr_data, "fernet_key": encrypted_sessionkey})

# Receives Certificate issued by CA
@app.route('/receive_cert', methods=['POST'])
def receive_cert():
    data = request.get_json()
    return jsonify(save_signed_cert(data['cert']))

# Check if a Certificate has been issued to this device before, and if yes, return encrypted cert with the encrypted key.
@app.route('/check_cert', methods=['GET'])
def check_cert():
    # Get the 'time' parameter from the request (defaults to None if not provided)
    unix_time = request.args.get('time')
    
    if not unix_time:
        return jsonify({"status": "error", "message": "Missing time parameter"}), 400

    # Ensure the time parameter is an integer
    try:
        unix_time = int(unix_time)
    except ValueError:
        return jsonify({"status": "error", "message": "Invalid time format"}), 400
    
    if os.path.exists(CLIENT_SIGNED_CERT_PATH):
        with open(CLIENT_SIGNED_CERT_PATH, "r") as cert_file:
            cert_data = cert_file.read()

        encrypted_combined_data, encrypted_session_key, signed_encrypted_combined_data = combine_and_encrypt(cert_data, unix_time)

        return jsonify({"status": "cert_exists", "combined_data": encrypted_combined_data, "fernet_key": encrypted_session_key, "signed_combined_data": signed_encrypted_combined_data})
    else:
        return jsonify({"status": "no_cert"})

# Get the public key of this device.
@app.route('/get_public_key', methods=['GET'])
def get_public_key_endpoint():
    public_key = get_public_key()
    if public_key:
        return jsonify({"status": "success", "public_key": public_key})
    else:
        return jsonify({"status": "error", "message": "Public key not found"}), 404

# Part 3: Sends Encrypted AES Key
@app.route('/get_encrypted_key', methods=['GET'])
def get_encrypted_key():
    global fernet_key
    fernet_key = gen_key()
    encrypted_key = encrypt_key(fernet_key)
    return jsonify({"encrypted_key": encrypted_key})

# Verify and check if C2 Cert is changed.
@app.route('/verify_c2_cert', methods=['POST'])
def verify_c2_cert():
    global fernet_key
    data = request.get_json()
    encrypted_cert = data.get("cert")
    decrypted_cert = decrypt_text(encrypted_cert, fernet_key)

    if verify_certificate(decrypted_cert):
        return jsonify({"status": "valid"})
    else:
        delete_existing_files()
        save_new_root_cert(decrypted_cert)
        return jsonify({"status": "new_cert_needed"})

# Receive JwT Token for use.
@app.route('/receive_token', methods=['POST'])
def receive_encrypted_data():
    global JwT_token
    data = request.get_json()
    combined_encrypted_data = data.get('data')
    if not combined_encrypted_data:
        return jsonify({"status": "error", "message": "No data provided"}), 400
    JwT_token = decrypt_and_get_jwt_token(combined_encrypted_data)

    return jsonify({"status": "success", "message": "Data received successfully"})

@app.route("/store_data", methods=["POST"])
def store_data():
    data = request.get_json()
    if not data or 'type' not in data or 'content' not in data:
        return jsonify({"status": "error", "message": "Invalid data"}), 400

    data_type = data['type']
    # content = data['content']
    data['content'] = constructDataResponse(data['content'], data['type'], gen_key(), JwT_token)
    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
    file_name = f"{data_type}_{timestamp}.json"
    file_path = os.path.join(TEMP_STORAGE, file_name)

    with open(file_path, 'w') as file:
        json.dump(data, file)

    return jsonify({"status": "success", "message": "Data stored successfully"}), 200

@app.route("/retrieve_data", methods=["GET"])
def retrieve_data():
    files = os.listdir(TEMP_STORAGE)
    if not files:
        return jsonify({"status": "error", "message": "No data available"}), 404

    data_to_send = []
    for file_name in files:
        file_path = os.path.join(TEMP_STORAGE, file_name)
        with open(file_path, 'r') as file:
            data = json.load(file)
            data_to_send.append(data)
        os.remove(file_path)

    return jsonify({"status": "success", "data": data_to_send}), 200

@app.route('/receive_script_and_key', methods=['POST'])
def receive_script_and_key():
    global fernet_key
    data = request.get_json()
    response = decrypt_and_reencrypt_script(data, fernet_key)
    return jsonify(response)

@app.route("/update_resolution", methods=["POST"])
def update_resolution():
    global screen_width
    global screen_height
    
    data = request.get_json()
    if(screen_width == 0 and screen_height == 0):
         screen_width = data["Width"]
         screen_height = data["Height"]
         
    response = {"Width" : screen_width, "Height" : screen_height}
    
    return jsonify(response), 200
    
@app.route("/get_resolution", methods=["GET"])
def get_resolution():
    global screen_width
    global screen_height
    
    response = {"Width" : screen_width, "Height" : screen_height}
    
    return jsonify(response), 200

@app.route("/update_calibration", methods=["POST"])
def update_calibration():
    global calibration_ready
    global calibration_position

    data = request.get_json()
    calibration_ready = data["Status"]
    calibration_position = data["Position"]
    response = {"Status" : calibration_ready, "Position" : calibration_position}
    
    return jsonify(response), 200
    
@app.route("/get_calibration", methods=["GET"])
def get_calibration():
    global calibration_ready
    global calibration_position
    
    response = {"Status" : calibration_ready, "Position" : calibration_position}
    
    return jsonify(response), 200

@app.route('/upload', methods=['POST'])
def upload_image():
    try:
        data = request.get_json()
        image_name = data['image_name']
        image_data = data['image_data']
        print(f"Received image: {image_name}")

        # Decode the base64 string to bytes
        image_bytes = base64.b64decode(image_data)
        print("Image data decoded from base64.")

        # Save the image locally
        image_path = os.path.join(UPLOAD_FOLDER, image_name)
        with open(image_path, 'wb') as image_file:
            image_file.write(image_bytes)
        print(f"Image saved: {image_path}")
        
        # Save the image to Webcam Folder
        image_path2 = os.path.join(WEBCAM_FOLDER, image_name)
        with open(image_path2, 'wb') as image_file2:
            image_file2.write(image_bytes)
        print(f"Image saved: {image_path2}")

        return jsonify({"message": "Image uploaded successfully", "path": image_path}), 200
    except Exception as e:
        print(f"Error: {e}")
        return jsonify({"error": str(e)}), 400
    
# Directory to store uploaded log files
LOG_STORAGE = '/home/pi/received_logs'
if not os.path.exists(LOG_STORAGE):
    os.makedirs(LOG_STORAGE)

# File name to overwrite each time a new log is uploaded
LOG_FILE_NAME = 'keystroke_log.log'

@app.route('/upload_log', methods=['POST'])
def upload_log():
    try:
        data = request.get_json()
        log_name = data.get('log_name', 'keystroke_log')
        log_data_base64 = data['log_data']

        # Decode the log data from base64
        log_data = base64.b64decode(log_data_base64).decode('utf-8')

        # Define the path where the log file will be saved
        file_path = os.path.join(LOG_STORAGE, LOG_FILE_NAME)

        # Overwrite the log file with the new content
        with open(file_path, 'w') as log_file:
            log_file.write(log_data)

        print(f"Log file saved and overwritten: {file_path}")
        return jsonify({"message": "Log uploaded successfully", "path": file_path}), 200
    except Exception as e:
        print(f"Error: {e}")
        return jsonify({"error": str(e)}), 400

if __name__ == "__main__":
    app.run(host='0.0.0.0', debug=True)
