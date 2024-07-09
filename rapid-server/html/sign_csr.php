<?php
require 'vendor/autoload.php';
require_once("Fernet/Fernet.php");
use Fernet\Fernet;
use MongoDB\Client as MongoDBClient;

define('PRIVATE_KEY_PATH', '/var/www/keys/private_rsa.key');
define('CERT_PATH', '/var/www/keys/root_ca.crt');

function respond($status, $message, $cert = null) {
    $response = ['status' => $status, 'message' => $message];
    if ($cert) {
        $response['cert'] = $cert;
    }
    echo json_encode($response);
    exit;
}

// MongoDB connection
$mongoClient = new MongoDBClient("mongodb://mongodb:27017");
$database = $mongoClient->selectDatabase(getenv('MONGO_DB'));
$collection = $database->selectCollection('cert_data');

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

// Load the CA certificate
$ca_cert = file_get_contents(CERT_PATH);
if (!$ca_cert) {
    error_log('Failed to read CA certificate.');
    respond('error', 'Failed to read CA certificate.');
}

// Parse the CA private key
$private_key_resource = openssl_pkey_get_private($ca_private_key, $ca_key_passphrase);
if (!$private_key_resource) {
    error_log('Failed to parse CA private key: ' . openssl_error_string());
    respond('error', 'Failed to parse CA private key.');
}

// Parse the CA certificate
$ca_cert_resource = openssl_x509_read($ca_cert);
if (!$ca_cert_resource) {
    error_log('Failed to parse CA certificate: ' . openssl_error_string());
    respond('error', 'Failed to parse CA certificate.');
}

// Retrieve the POST data
$input = file_get_contents('php://input');
if (!$input) {
    error_log('No input received.');
    respond('error', 'No input received.');
}

$data = json_decode($input, true);

if (!$data || !isset($data['csr']) || !isset($data['fernet_key'])) {
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

// Decode the CSR data
$csr_data_encrypted = base64_decode($data['csr']);

// Decrypt the CSR data
try {
    $csr_data = $fernet->decode($data['csr']);
} catch (Exception $e) {
    error_log('Failed to decode CSR data: ' . $e->getMessage());
    respond('error', 'Failed to decode CSR data.');
}

// Validate the CSR data format
if (strpos($csr_data, '-----BEGIN CERTIFICATE REQUEST-----') === false || strpos($csr_data, '-----END CERTIFICATE REQUEST-----') === false) {
    error_log('Invalid CSR format.');
    respond('error', 'Invalid CSR format.');
}

$csr = openssl_csr_get_subject($csr_data);
$uuid = $csr['CN'] ?? null;

if (!$uuid) {
    error_log('UUID not found in CSR.');
    respond('error', 'UUID not found in CSR.');
}

// Validate UUID format (adjust the pattern to match your UUID format)
$uuidPattern = '/^[0-9a-fA-F\-]+$/';  // Adjust this pattern based on your specific UUID format
if (!preg_match($uuidPattern, $uuid)) {
    error_log('Invalid UUID format.');
    respond('error', 'Invalid UUID format.');
}


// Define the certificate configuration
$config = [
    'digest_alg' => 'sha256',
    'x509_extensions' => 'v3_ca',
    'req_extensions' => 'v3_req',
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_EC,  // Make sure you are using EC keys. If not, adjust accordingly.
    'encrypt_key' => false,
    'config' => '/etc/ssl/openssl_myapp.cnf'  // Path to your permanent config file
];

// Sign the CSR with the CA's private key using the specified configuration
$signed_cert = openssl_csr_sign($csr_data, $ca_cert_resource, $private_key_resource, 365, $config);

if (!$signed_cert) {
    error_log('Failed to sign CSR: ' . openssl_error_string());
    respond('error', 'Failed to sign CSR.');
}

// Export the signed certificate
$signed_cert_out = '';
if (!openssl_x509_export($signed_cert, $signed_cert_out)) {
    error_log('Failed to export signed certificate: ' . openssl_error_string());
    respond('error', 'Failed to export signed certificate.');
}

// Convert current time to Asia/Singapore timezone and then to MongoDB\BSON\UTCDateTime
$time = new DateTime('now', new DateTimeZone('Asia/Singapore'));
$timeString = $time->format('Y-m-d H:i:s');

// Save the UUID and certificate to MongoDB
$document = [
    'uuid' => $uuid,
    'certificate' => $signed_cert_out,
    'created_at' => $timeString,
    'revoked' => false
];

$insertResult = $collection->insertOne($document);
if (!$insertResult->isAcknowledged()) {
    error_log('Failed to save certificate data to MongoDB.');
    respond('error', 'Failed to save certificate data to MongoDB.');
}

// Respond with the signed certificate
respond('success', 'Certificate signed successfully.', base64_encode($signed_cert_out));
?>