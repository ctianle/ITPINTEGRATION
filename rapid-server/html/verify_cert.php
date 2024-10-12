<?php
require_once("Fernet/Fernet.php");
require 'vendor/autoload.php';
use Fernet\Fernet;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define('ROOT_CA_CERT_PATH', '/var/www/keys/root_ca.crt');
define('PRIVATE_KEY_PATH', '/var/www/keys/private_rsa.key');

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

function respond($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

function fetchStudentSessions($manager, $studentId) {
    global $dbName;
    // Create a query to find the student by StudentId
    $query = new MongoDB\Driver\Query(['StudentId' => (int)$studentId]);
    
    // Execute the query against the 'Students' collection in the specified database
    $cursor = $manager->executeQuery($dbName . '.Students', $query);

    $sessions = [];
    
    // Iterate through the student documents
    foreach ($cursor as $student) {
        // Ensure the document has 'SessionIds' and it's an array
        if (isset($student->SessionId)) {
            // Fetch the session details for this SessionId
            $sessionQuery = new MongoDB\Driver\Query(['SessionId' => $student->SessionId]);

            // Execute the query against the 'Sessions' collection
            $sessionCursor = $manager->executeQuery($dbName . '.Sessions', $sessionQuery);

            // Add each session document to the $sessions array
            foreach ($sessionCursor as $session) {
                $sessions[] = $session;
            }
        }
    }
    return $sessions;
}

function checkSessionTiming($session) {
    // Ensure the server is using the correct timezone (e.g., Asia/Singapore or GMT+8)
    date_default_timezone_set('Asia/Singapore'); 

    // Get the current time
    $currentTime = new DateTime();

    // Ensure StartTime and EndTime exist and are valid MongoDB\BSON\UTCDateTime objects
    if (isset($session->StartTime) && $session->StartTime instanceof MongoDB\BSON\UTCDateTime &&
        isset($session->EndTime) && $session->EndTime instanceof MongoDB\BSON\UTCDateTime) {
        
        // Convert MongoDB\BSON\UTCDateTime to PHP DateTime and adjust to the server's timezone (GMT+8)
        $sessionStartTime = $session->StartTime->toDateTime()->setTimezone(new DateTimeZone('Asia/Singapore'));
        $sessionEndTime = $session->EndTime->toDateTime()->setTimezone(new DateTimeZone('Asia/Singapore'));

        // Modify the session start time to 15 minutes prior
        $sessionStartTime->modify('-15 minutes');

        // Check if the current time is between the adjusted start time and the end time
        if ($currentTime >= $sessionStartTime && $currentTime <= $sessionEndTime) {
            return true;
        } 
    }

    return false;
}

// Function to generate JWT token
function generate_jwt($client_uuid, $session_id) {
    $issued_at = time();
    $expiration_time = $issued_at + 3600; // jwt valid for 1 hour from the issued time
    $payload = array(
        "iat" => $issued_at,
        "exp" => $expiration_time,
        "jti" => bin2hex(random_bytes(16)),
        "client_uuid" => $client_uuid,
        "session_id" => $session_id
    );

    // Get the secret key from environment variables
    $secret_key = getenv('JWT_SECRET');
    if (!$secret_key) {
        error_log('JWT secret key is not set in environment variables.');
        respond('error', 'JWT secret key is not set.');
    }

    $jwt = JWT::encode($payload, $secret_key, 'HS256');
    return $jwt;
}

function main($manager, $client_uuid) {
    $client_id_parts = explode("-", $client_uuid);
    $student_id = $client_id_parts[0]; // Extract the student ID
    $sessions = fetchStudentSessions($manager, $student_id);

    foreach ($sessions as $session) {
        if (checkSessionTiming($session)) {
            $jwt_token = generate_jwt($client_uuid, $session->SessionId);
            return $jwt_token;
        }
    }
    error_log('No suitable session found for issuing JWT to Device: ' . $client_uuid);
    return null;
}


// Fetch the CA key passphrase from environment variable
$ca_key_passphrase = getenv('CA_KEY_PASSPHRASE');
if (!$ca_key_passphrase) {
    error_log('CA key passphrase environment variable is not set.');
    respond('error', 'CA key passphrase environment variable is not set.');
}

// Load the CA private key
$ca_private_key = file_get_contents(PRIVATE_KEY_PATH);
if (!$ca_private_key) {
    error_log('Failed to read CA private key.');
    respond('error', 'Failed to read CA private key.');
}

// Parse the CA private key
$private_key_resource = openssl_pkey_get_private($ca_private_key, $ca_key_passphrase);
if (!$private_key_resource) {
    error_log('Failed to parse CA private key: ' . openssl_error_string());
    respond('error', 'Failed to parse CA private key.');
}

// Retrieve the POST data
$input = file_get_contents('php://input');
if (!$input) {
    error_log('No input received.');
    respond('error', 'No input received.');
}

$data = json_decode($input, true);
if (!$data || !isset($data['combined_data']) || !isset($data['fernet_key']) || !isset($data['signed_combined_data'])) {
    error_log('Invalid input format.');
    respond('error', 'Invalid input format.');
}

// Decode the Fernet key
$fernet_key_encrypted = base64_decode($data['fernet_key']);

// Decrypt the Fernet key using the CA private key
if (!openssl_private_decrypt($fernet_key_encrypted, $fernet_key, $private_key_resource)) {
    error_log('Failed to decrypt Fernet key: ' . openssl_error_string());
    respond('error', 'Failed to decrypt Fernet key.');
}

$fernet = new Fernet($fernet_key);

// Decode the combined data
try {
    $combined_data_json = $fernet->decode($data['combined_data']);
    $combined_data = json_decode($combined_data_json, true);
    if (!$combined_data) {
        throw new Exception('Invalid JSON format');
    }
} catch (Exception $e) {
    error_log('Failed to decode combined data: ' . $e->getMessage());
    respond('error', 'Failed to decode combined data.');
}

// Extract the signed message, certificate data, and original message
$signed_message = $combined_data['signed_message'];
$cert_data = $combined_data['cert_data'];
$original_message = $combined_data['message'];

// MongoDB connection using native driver
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// Check if the certificate has been revoked
$query = new MongoDB\Driver\Query(['certificate' => $cert_data]);
$cursor = $manager->executeQuery($dbName . '.cert_data', $query);
$cert_document = current($cursor->toArray());

// Check if certificate revoked
if ($cert_document && isset($cert_document->revoked) && $cert_document->revoked) {
    respond('error', 'Certificate has been revoked.');
}

// Load the root CA certificate
$root_ca_cert = file_get_contents(ROOT_CA_CERT_PATH);
if (!$root_ca_cert) {
    error_log('Failed to read root CA certificate.');
    respond('error', 'Failed to read root CA certificate.');
}

// Save the provided certificate temporarily for verification
$temp_cert_path = tempnam(sys_get_temp_dir(), 'client_cert.pem');
file_put_contents($temp_cert_path, $cert_data);

// Create temporary files for CA certificate and client certificate
$temp_ca_cert_path = tempnam(sys_get_temp_dir(), 'ca_cert.pem');
file_put_contents($temp_ca_cert_path, $root_ca_cert);

$verify_cmd = "openssl verify -CAfile $temp_ca_cert_path $temp_cert_path 2>&1";
exec($verify_cmd, $output, $return_var);

// Clean up temporary files
unlink($temp_cert_path);
unlink($temp_ca_cert_path);

if ($return_var !== 0) {
    error_log('Certificate verification failed: ' . implode("\n", $output));
    respond('error', 'Certificate verification failed.');
}

// Extract the public key from the client certificate
$cert = openssl_x509_read($cert_data);
$public_key_resource = openssl_pkey_get_public($cert);
if (!$public_key_resource) {
    error_log('Failed to extract public key from client certificate: ' . openssl_error_string());
    respond('error', 'Failed to extract public key from client certificate.');
}

// Verify the signature of the encrypted combined data
$signed_encrypted_combined_data = base64_decode($data['signed_combined_data']);
$is_valid_signature = openssl_verify($data['combined_data'], $signed_encrypted_combined_data, $public_key_resource, OPENSSL_ALGO_SHA256);
if ($is_valid_signature !== 1) {
    error_log('Invalid signature for encrypted combined data: ' . openssl_error_string());
    respond('error', 'Invalid signature for encrypted combined data.');
}

// Verify the signature
$is_valid_signature = openssl_verify($original_message, base64_decode($signed_message), $public_key_resource, OPENSSL_ALGO_SHA256);
if ($is_valid_signature !== 1) {
    error_log('Invalid signature: ' . openssl_error_string());
    respond('error', 'Invalid signature.');
}

// Verify the timestamp to prevent replay attacks
$original_message_parts = explode('.', $original_message);
if (count($original_message_parts) < 2) {
    respond('error', 'Original message format is invalid.');
}

$timestamp = (int)$original_message_parts[count($original_message_parts) - 1];

$current_time = time();

$allowed_time_difference = 300; // 5 minutes
if (abs($current_time - $timestamp) > $allowed_time_difference) {
    respond('error', 'Timestamp is outside the allowed time window.');
}

$client_uuid = $original_message_parts[0]; 

// Generate JWT token
$jwt_token = main($manager, $client_uuid);

if (!$jwt_token) { // Checking if there is NOT a JWT token
    exit;
}
// Generate a new Fernet key
$new_fernet_key = Fernet::generateKey();

// Encrypt the JWT token with the new Fernet key
$new_fernet = new Fernet($new_fernet_key);
$encrypted_jwt_token = $new_fernet->encode($jwt_token);

// Generate ephemeral ECDH keys
$ecdh_private_key_cmd = "openssl ecparam -name secp256r1 -genkey -noout";
$ecdh_public_key_cmd = "openssl ec -pubout";
$private_key = shell_exec($ecdh_private_key_cmd);
$public_key = shell_exec("echo \"$private_key\" | $ecdh_public_key_cmd");

// Extract the client's public key in PEM format
$public_key_details = openssl_pkey_get_details($public_key_resource);
$client_public_key_pem = $public_key_details['key'];

// Write the client's public key to a temporary file
$temp_client_public_key_path = tempnam(sys_get_temp_dir(), 'client_pub.pem');
file_put_contents($temp_client_public_key_path, $client_public_key_pem);

// Generate the shared secret using ECDH
$shared_secret_cmd = "echo \"$private_key\" | openssl pkeyutl -derive -inkey /dev/stdin -peerkey $temp_client_public_key_path";
$shared_secret = shell_exec($shared_secret_cmd);

// Clean up temporary files
unlink($temp_client_public_key_path);

$shared_secret_key = hash_hkdf('sha256', $shared_secret, 32, '', '');

// Encrypt the Fernet key with the shared secret using Fernet
$shared_secret_fernet = new Fernet(base64_encode($shared_secret_key));
$encrypted_fernet_key = $shared_secret_fernet->encode($new_fernet_key);

// Combine the ephemeral public key, encrypted Fernet key, and encrypted JWT token
$combined_encrypted_data = json_encode([
    'ephemeral_public_key' => base64_encode($public_key),
    'encrypted_fernet_key' => $encrypted_fernet_key,
    'encrypted_jwt_token' => $encrypted_jwt_token,
]);

respond('success', $combined_encrypted_data);

?>