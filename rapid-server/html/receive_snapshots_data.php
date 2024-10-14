<?php
// c2_server.php

// Set the default timezone to Asia/Singapore
date_default_timezone_set('Asia/Singapore');

// Include MongoDB library
require 'vendor/autoload.php';

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

require_once("Fernet/Fernet.php"); 
use Fernet\Fernet;

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new Manager($mongoDBConnectionString);

// Function to decode and verify JWT token
function Verify_Token($jwt_token) {
    // Get the secret key from environment variables
    $secret_key = getenv('JWT_SECRET');
    if (!$secret_key) {
        error_log('JWT secret key is not set in environment variables.');
        exit;
    }

    try {
        // Decode the JWT token
        $decoded = JWT::decode($jwt_token, new Key($secret_key, 'HS256'));

        // Convert the decoded object to an array
        $decoded_array = (array) $decoded;

        // Get the current timestamp
        $current_time = time();

        // Check the validity of the timestamp
        if ($current_time < $decoded_array['iat'] || $current_time > $decoded_array['exp']) {
            // Invalid timestamp, stop processing
            respond('error', "Invalid Token Timestamp");
            exit;
        }
        if (!validateUUID($decoded_array['client_uuid'])) {
            exit; // Stop further execution if the UUID format is not valid
        }
        // Token is valid
        return true;

    } catch (Exception $e) {
        // Handle errors
        error_log('JWT decode error: ' . $e->getMessage());
        exit;
    }
}

function getUUID($jwt_token){
    // Get the secret key from environment variables
    $secret_key = getenv('JWT_SECRET');
    if (!$secret_key) {
        error_log('JWT secret key is not set in environment variables.');
        exit;
    }

    try {
        // Decode the JWT token
        $decoded = JWT::decode($jwt_token, new Key($secret_key, 'HS256'));

        // Convert the decoded object to an array
        $decoded_array = (array) $decoded;

        return $decoded_array['client_uuid'];

    } catch (Exception $e) {
        // Handle errors
        error_log('JWT decode error: ' . $e->getMessage());
        exit;
    }
}

function getSessionID($jwt_token){
    // Get the secret key from environment variables
    $secret_key = getenv('JWT_SECRET');
    if (!$secret_key) {
        error_log('JWT secret key is not set in environment variables.');
        exit;
    }

    try {
        // Decode the JWT token
        $decoded = JWT::decode($jwt_token, new Key($secret_key, 'HS256'));

        // Convert the decoded object to an array
        $decoded_array = (array) $decoded;

        return $decoded_array['session_id'];

    } catch (Exception $e) {
        // Handle errors
        error_log('JWT decode error: ' . $e->getMessage());
        exit;
    }
}
function validateUUID($uuid) {
    $regex = '/^\d{7}-[a-f0-9]{64}$/';

    if (preg_match($regex, $uuid)) {
        return true; // UUID is valid
    } else {
        error_log('Invalid UUID format.');
        return false; // UUID is not valid
    }
}

// Log error function
function logError($error) {
    $logFile = '/var/logs/myapp/php_errors.log';
    $error_message = "\n" . date('d-m-Y H:i:s') . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
}

// Handle POST request to receive data from PowerShell script
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get the raw POST data
    $postData = file_get_contents('php://input');

    // Decode JSON data
    $encryptedArray = json_decode($postData, true);
    //========================================
    // RSA Encryption (Decrypting Fernet Key)
    //========================================

    // Fetch the CA key passphrase from environment variable
    $ca_key_passphrase = getenv('CA_KEY_PASSPHRASE');
    if (!$ca_key_passphrase) {
        error_log('CA key passphrase environment variable is not set.');
    }

    // Load the CA private key
    $ca_private_key = file_get_contents("/var/www/keys/private_rsa.key");
    if (!$ca_private_key) {
        error_log('Failed to read CA private key.');
    }

    // Parse the CA private key
    $RSA_privatekey = openssl_pkey_get_private($ca_private_key, $ca_key_passphrase);
    if (!$RSA_privatekey) {
        error_log('Failed to parse CA private key: ' . openssl_error_string());
    }

    $encryptedfernetkey = base64_decode($encryptedArray[5]);
    openssl_private_decrypt($encryptedfernetkey, $fernetkey, $RSA_privatekey);
    //========================================
    //     Fernet (Symmetric Encryption)
    //  Decrypting the rest of the JSON Data
    //========================================
    $fernet = new Fernet($fernetkey); //Fernet Key
    $category = $fernet->decode($encryptedArray[3]); // Content
    $data = $fernet->decode($encryptedArray[4]);
    $JwT_Token = $fernet->decode($encryptedArray[7]);
    Verify_Token($JwT_Token);
    $UUID = getUUID($JwT_Token);
    $ProctorSessionID = getSessionID($JwT_Token);

    $newData = [
        'timestamp' => new UTCDateTime((new DateTime('now', new DateTimeZone('Asia/Singapore')))->getTimestamp() * 1000), // Timestamp in milliseconds, Asia/Singapore timezone
        'datatype' => $category, 
        'content' => $data,
        'UUID' => $UUID,
        'ProctorSessionID' => $ProctorSessionID   
    ];

        // Insert the received data into MongoDB
        try {
            $bulk = new BulkWrite();
            $bulk->insert($newData);
            $result = $manager->executeBulkWrite("$dbName.Snapshots", $bulk);

            if ($result->getInsertedCount() !== 1) {
                logError('Failed to insert data into MongoDB.');
            }

            // Send response back to the PowerShell script
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Data received successfully']);
        } catch (MongoDB\Driver\Exception\Exception $e) {
            logError('MongoDB Exception: ' . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert data into MongoDB']);
        }
} 

?>
