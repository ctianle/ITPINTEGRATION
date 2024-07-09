from flask import Flask, jsonify, request
from functions import *

app = Flask(__name__)

HTTP_METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE', 'PATCH', 'BREW']

PuK = False
fernet_key = None
JwT_token = None

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
    if os.path.exists(CLIENT_SIGNED_CERT_PATH):
        with open(CLIENT_SIGNED_CERT_PATH, "r") as cert_file:
            cert_data = cert_file.read()

        encrypted_combined_data, encrypted_session_key, signed_encrypted_combined_data = combine_and_encrypt(cert_data)

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


if __name__ == "__main__":
    app.run(host='0.0.0.0', debug=True)
