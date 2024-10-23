import base64, os, rsa, time, json
from cryptography.fernet import Fernet
from getmac import get_mac_address as gma
import hashlib
import subprocess
from cryptography.hazmat.primitives.kdf.hkdf import HKDF
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives.serialization import load_pem_public_key, load_pem_private_key, load_der_public_key
from cryptography.hazmat.primitives import serialization, hashes
from cryptography import x509
from cryptography.x509.oid import NameOID
from cryptography.hazmat.backends import default_backend
import xml.etree.ElementTree as ET
from cryptography.hazmat.primitives.asymmetric import rsa as rsa1
from cryptography.hazmat.primitives.asymmetric import padding as padding1

# variables
TRIGGER = 0
UPDATEINTERVAL = False
blacklist = ['telegram', 'whatsapp', 'spotify']
CLIENT_PRIVATE_KEY_PATH = "/etc/ssl/client_private.key"
CLIENT_PUBLIC_KEY_PATH = "/etc/ssl/client_public.key"
CLIENT_CSR_PATH = "/etc/ssl/client.csr"
CLIENT_SIGNED_CERT_PATH = "/etc/ssl/client_cert.pem"
UUID = None
STUDENT_ID_FILE = '/etc/ssl/studentid.txt'
ROOT_CERT_PATH = '/etc/ssl/root_ca.pem'

# function to decode base64
def decodebase64(base64_message):
    base64_bytes = base64_message.encode('utf-16le')
    message_bytes = base64.b64decode(base64_bytes)
    message = message_bytes.decode('utf-16le')
    return message

def encodebase64(message):
    message_bytes = message.encode('ascii')
    base64_bytes = base64.b64encode(message_bytes)
    base64_message = base64_bytes.decode('ascii')
    return base64_message

# function to process the data received from the Student's PC
def processing(data, category):
    global TRIGGER, UPDATEINTERVAL
    try:
        if category == 'AWD':
            if data in blacklist:
                TRIGGER += 1
                UPDATEINTERVAL = True
        if category == 'AMD':
            if int(data) > 4:
                TRIGGER += 1
                UPDATEINTERVAL = True
        if category == 'PL' or category == 'OW':
            if any(element in data for element in blacklist):
                TRIGGER += 1
                UPDATEINTERVAL = True
    except Exception as e:
        print(e)

# Constructing JSON response, pending encryption integration
def constructDataResponse(data, category, key, JwT_token):
    global TRIGGER, UPDATEINTERVAL
    response = {}

    # encrypt data and add to response dictionary
    response["1"] = encrypt_text(str(TRIGGER), key)
    response["2"] = encrypt_text(str(UPDATEINTERVAL), key)
    response["3"] = encrypt_text(category, key)

    if category in ['OW', 'PL']:
        data_list = []
        for item in data:
            data_list.append(encrypt_text(item, key))
        response["4"] = data_list
    else:
        response["4"] = encrypt_text(data, key)
    response["5"] = encrypt_key(key)
    response["6"] = encrypt_text(generate_uuid(), key)
    response["7"] = encrypt_text(JwT_token, key)

    UPDATEINTERVAL = False
    return response

def constructPingResponse(token, key):
    response = {}
    response["1"] = encrypt_text(str(token), key)
    response["2"] = encrypt_key(key)
    return response

def get_cpu_serial():
    try:
        cpu_serial = subprocess.check_output("cat /proc/cpuinfo | grep Serial | cut -d ' ' -f 2", shell=True).strip()
        return cpu_serial.decode()
    except:
        return "0000000000000000"

def get_disk_serial():
    try:
        disk_serial = subprocess.check_output("lsblk -o SERIAL | grep -v 'SERIAL'", shell=True).strip()
        return disk_serial.decode()
    except:
        return "0000000000000000"

def get_mac_address():
    try:
        mac_address = subprocess.check_output("cat /sys/class/net/usb0/address", shell=True).strip()
        return mac_address.decode()
    except:
        return "00:00:00:00:00:00"

def generate_uuid():
    if os.path.exists(STUDENT_ID_FILE):
        with open(STUDENT_ID_FILE, "r") as f:
            studentid = f.read().strip()
    else:
        studentid='uninitialized'
    cpu_serial = get_cpu_serial()
    disk_serial = get_disk_serial()
    mac_address = get_mac_address()
    uniquehardwarestring = f"{cpu_serial}{disk_serial}{mac_address}"
    uniquehash = hashlib.sha256(uniquehardwarestring.encode()).hexdigest()
    uuid = f"{studentid}-{uniquehash}"
    return uuid

def constructMacResponse():
    response = {}
    response["UUID"] = generate_uuid()
    return response

# Symmetric Encryption
def gen_key():
    key = Fernet.generate_key()
    return key

def store_public_key(key):
    global PUBLICKEY
    # base64 decode public key received from the server
    decodedKey = decodebase64(key)
    # convert the string from payload into an RSA key and store it in the global variable
    PUBLICKEY = rsa.PublicKey.load_pkcs1_openssl_pem(decodedKey)
    return

def encrypt_text(plaintext, key):
    encodedtext = plaintext.encode('utf-8')
    fernet = Fernet(key)
    ciphertext = fernet.encrypt(encodedtext)
    return ciphertext.decode('utf-8')

def decrypt_text(ciphertext, key):
    encodedciphertext = ciphertext.encode('utf-8')
    fernet = Fernet(key)
    plaintext = fernet.decrypt(encodedciphertext)
    return plaintext.decode('utf-8')

def encrypt_key(key):
    global PUBLICKEY
    encryptedkey = rsa.encrypt(key, PUBLICKEY)
    return base64.b64encode(encryptedkey).decode('utf-8')

# Ensure the directories exist
os.makedirs('/etc/ssl', exist_ok=True)

# Generate ECC key pair and CSR if not already generated
def generate_csr_and_key():
    global UUID
    UUID = generate_uuid()
    if not os.path.exists(CLIENT_PRIVATE_KEY_PATH) or not os.path.exists(CLIENT_CSR_PATH):
        private_key = ec.generate_private_key(ec.SECP256R1(), default_backend())
        public_key = private_key.public_key()

        with open(CLIENT_PRIVATE_KEY_PATH, "wb") as f:
            f.write(private_key.private_bytes(
                encoding=serialization.Encoding.PEM,
                format=serialization.PrivateFormat.TraditionalOpenSSL,
                encryption_algorithm=serialization.NoEncryption()
            ))

        with open(CLIENT_PUBLIC_KEY_PATH, "wb") as f:
            f.write(public_key.public_bytes(
                encoding=serialization.Encoding.PEM,
                format=serialization.PublicFormat.SubjectPublicKeyInfo
            ))

        csr = x509.CertificateSigningRequestBuilder().subject_name(x509.Name([
            x509.NameAttribute(NameOID.COMMON_NAME, UUID),
            x509.NameAttribute(NameOID.ORGANIZATION_NAME, u"SIT"),
            x509.NameAttribute(NameOID.COUNTRY_NAME, u"SG"),
        ])).sign(private_key, hashes.SHA256(), default_backend())
        with open(CLIENT_CSR_PATH, "wb") as f:
            f.write(csr.public_bytes(serialization.Encoding.PEM))

# Function to provide CSR content
def get_csr(studentid):
    if not os.path.exists(STUDENT_ID_FILE):
        with open(STUDENT_ID_FILE, "w") as f:
            f.write(studentid)

    generate_csr_and_key()
    with open(CLIENT_CSR_PATH, "rb") as f:
        csr_data = f.read().decode('utf-8')
    sessionkey = gen_key()
    encrypted_csr_data = encrypt_text(csr_data, sessionkey)
    encrypted_sessionkey = encrypt_key(sessionkey)

    return encrypted_csr_data, encrypted_sessionkey

# Function to save signed certificate
def save_signed_cert(cert):
    signed_cert = base64.b64decode(cert)
    with open(CLIENT_SIGNED_CERT_PATH, "wb") as f:
        f.write(signed_cert)
    return {'status': 'success'}

# Allow Powershell Script to retrieve public key for encryption
def get_public_key():
    if os.path.exists(CLIENT_PUBLIC_KEY_PATH):
        with open(CLIENT_PUBLIC_KEY_PATH, "rb") as key_file:
            public_key = key_file.read().decode('utf-8')
        return public_key
    else:
        return None

# Part 3: Verify C2 certificate to check if it has changed .
def verify_certificate(cert_data):
    stored_cert = None  # Initialize stored_cert to None
    if os.path.exists(ROOT_CERT_PATH):
        with open(ROOT_CERT_PATH, "rb") as f:
            stored_cert = f.read()

    if stored_cert is None:
        return False  # or handle the case where the certificate doesn't exist

    return stored_cert == cert_data.encode('utf-8')

# Part 3: Delete existing related keypair and cert relating to old C2
def delete_existing_files():
    files = [CLIENT_PRIVATE_KEY_PATH, CLIENT_PUBLIC_KEY_PATH, CLIENT_CSR_PATH, CLIENT_SIGNED_CERT_PATH]
    for file in files:
        if os.path.exists(file):
            os.remove(file)

# Part 3: Save updated C2 Certificate
def save_new_root_cert(cert_data):
    with open(ROOT_CERT_PATH, "wb") as f:
        f.write(cert_data.encode('utf-8'))

def studentid_exists():
    return os.path.exists(STUDENT_ID_FILE)

# Function to sign a message with the private key
def sign_message(message, private_key_path=CLIENT_PRIVATE_KEY_PATH):
    with open(private_key_path, "rb") as key_file:
        private_key = load_pem_private_key(key_file.read(), password=None, backend=default_backend())
    signature = private_key.sign(message.encode(), ec.ECDSA(hashes.SHA256()))
    return base64.b64encode(signature).decode('utf-8')

# Function to generate a dynamic message for signing
def generate_dynamic_message(unix_time):
    timestamp = unix_time
    uuid = generate_uuid()
    message = f"{uuid}.{timestamp}"
    return message

# Function to combine and encrypt the signed message and certificate data
def combine_and_encrypt(cert_data, unix_time, private_key_path=CLIENT_PRIVATE_KEY_PATH):
    message = generate_dynamic_message(unix_time)
    signed_message = sign_message(message, private_key_path)

    combined_data = {
        "signed_message": signed_message,
        "cert_data": cert_data,
        "message": message
    }

    combined_data_json = json.dumps(combined_data)

    session_key = gen_key()
    encrypted_combined_data = encrypt_text(combined_data_json, session_key)
    encrypted_session_key = encrypt_key(session_key)

    signed_encrypted_combined_data = sign_message(encrypted_combined_data, private_key_path)
    return encrypted_combined_data, encrypted_session_key, signed_encrypted_combined_data


# Function to decrypt the Fernet key
def decrypt_fernet_key(ephemeral_public_key_pem, encrypted_fernet_key, private_key_path):
    # Load the ephemeral public key
    ephemeral_public_key = load_pem_public_key(ephemeral_public_key_pem.encode('utf-8'), backend=default_backend())

    # Load our private key
    with open(private_key_path, "rb") as key_file:
        private_key = load_pem_private_key(key_file.read(), password=None, backend=default_backend())

    # Perform ECDH key exchange to derive the shared secret
    shared_secret = private_key.exchange(ec.ECDH(), ephemeral_public_key)

    # Use the shared secret to derive the Fernet key
    derived_key = HKDF(
        algorithm=hashes.SHA256(),
        length=32,
        salt=None,
        info=b'',  # Removed the 'handshake data' to match the OpenSSL command
        backend=default_backend()
    ).derive(shared_secret)

    # Decrypt the Fernet key using the derived key
    fernet = Fernet(base64.urlsafe_b64encode(derived_key))
    decrypted_fernet_key = fernet.decrypt(encrypted_fernet_key.encode('utf-8'))

    return decrypted_fernet_key

# Function to decrypt the JWT token
def decrypt_jwt_token(encrypted_jwt_token, decrypted_fernet_key):
    fernet = Fernet(decrypted_fernet_key)
    decrypted_jwt_token = fernet.decrypt(encrypted_jwt_token.encode('utf-8'))
    return decrypted_jwt_token.decode('utf-8')

# Main function to handle the decryption process
def decrypt_and_get_jwt_token(response_data_str, private_key_path=CLIENT_PRIVATE_KEY_PATH):
    try:
        response_data = json.loads(response_data_str)
    except json.JSONDecodeError as e:
        raise ValueError("Response data is not valid JSON") from e
    ephemeral_public_key = response_data.get('ephemeral_public_key')
    encrypted_fernet_key = response_data.get('encrypted_fernet_key')
    encrypted_jwt_token = response_data.get('encrypted_jwt_token')


    if not all([ephemeral_public_key, encrypted_fernet_key, encrypted_jwt_token]):
        raise ValueError("Response data is missing required fields")

    # Base64 decode the ephemeral public key
    ephemeral_public_key_pem = base64.b64decode(ephemeral_public_key).decode('utf-8')

    # Decrypt the Fernet key
    decrypted_fernet_key = decrypt_fernet_key(ephemeral_public_key_pem, encrypted_fernet_key, private_key_path)

    # Decrypt the JWT token
    jwt_token = decrypt_jwt_token(encrypted_jwt_token, decrypted_fernet_key)

    return jwt_token

def decrypt_and_reencrypt_script(data, fernet_key):
    encrypted_script = data.get('encrypted_script')
    public_key = data.get('public_key')

    if not all([encrypted_script, public_key]):
        return {"status": "error", "message": "Missing data"}, 400

    encrypted_functions_script = base64.b64decode(encrypted_script)

    # Decrypt the script using the provided Fernet key
    fernet = Fernet(fernet_key)
    decrypted_script = fernet.decrypt(encrypted_functions_script).decode('utf-8')

    rsa_public_key = base64.b64decode(public_key).decode('utf-8')
    rsa_public_key = load_rsa_public_key_from_xml(rsa_public_key)

    # Define the maximum chunk size for RSA encryption with OAEP padding
    key_size = rsa_public_key.key_size // 8
    max_chunk_size = key_size - 11

    # Encrypt the decrypted script in chunks
    encrypted_script = encrypt_in_chunks(decrypted_script.encode('utf-8'), rsa_public_key, max_chunk_size)

    return {
        "re_encrypted_script": base64.b64encode(encrypted_script).decode('utf-8')
    }

def load_rsa_public_key_from_xml(xml_string):
    # Parse the XML string
    root = ET.fromstring(xml_string)
    modulus = root.find('Modulus').text
    exponent = root.find('Exponent').text

    # Convert from Base64 to integers
    modulus_bytes = base64.b64decode(modulus)
    exponent_bytes = base64.b64decode(exponent)
    modulus_int = int.from_bytes(modulus_bytes, byteorder='big')
    exponent_int = int.from_bytes(exponent_bytes, byteorder='big')

    # Create RSA public key
    public_key = rsa1.RSAPublicNumbers(exponent_int, modulus_int).public_key(default_backend())

    return public_key

def encrypt_in_chunks(data, rsa_public_key, chunk_size):
    encrypted_chunks = []
    for i in range(0, len(data), chunk_size):
        chunk = data[i:i + chunk_size]
        encrypted_chunk = rsa_public_key.encrypt(
            chunk,
            padding1.PKCS1v15()
        )
        encrypted_chunks.append(encrypted_chunk)
    return b''.join(encrypted_chunks)