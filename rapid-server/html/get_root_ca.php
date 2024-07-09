<?php
require 'vendor/autoload.php';
require_once("Fernet/Fernet.php");
use Fernet\Fernet;

// Define the path to the CA certificate and private key
define('CA_CERT_PATH', '/var/www/keys/root_ca.crt');
define('PRIVATE_KEY_PATH', '/var/www/keys/private_rsa.key');

function respond($status, $message, $cert = null) {
    $response = ['status' => $status, 'message' => $message];
    if ($cert) {
        $response['cert'] = $cert;
    }
    echo json_encode($response);
    exit;
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

if (!$data || !isset($data['key'])) {
    error_log('Invalid input format.');
    respond('error', 'Invalid input format.');
}

// Decode the Fernet key
$fernet_key_encrypted = base64_decode($data['key']);

// Decrypt the Fernet key using the CA private key
if (!openssl_private_decrypt($fernet_key_encrypted, $fernet_key, $private_key_resource)) {
    error_log('Failed to decrypt Fernet key: ' . openssl_error_string());
    respond('error', 'Failed to decrypt Fernet key.');
}

$fernet = new Fernet($fernet_key);

// Load the root CA certificate
$ca_cert = file_get_contents(CA_CERT_PATH);
if (!$ca_cert) {
    error_log('Failed to read CA certificate.');
    respond('error', 'Failed to read CA certificate.');
}

// Encrypt the CA certificate with the Fernet key
$encrypted_cert = $fernet->encode($ca_cert);

respond('success', 'CA certificate encrypted successfully.', $encrypted_cert);
?>
