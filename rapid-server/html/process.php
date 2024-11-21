<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\Driver\BulkWrite;
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
//====================================
//     Troubleshooting Parameters
//====================================
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//=============================================
//     MongoDB Connection & Credentials Set Up
//=============================================
use MongoDB\Client as MongoDBClient;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);
//====================================
//     Decode POST Request (JSON)
//====================================

// Takes raw data from the request
$json = file_get_contents('php://input');
//var_dump($json); //Testing & Troubleshooting Purposes

// Converts it into a PHP object
$rawdata = json_decode($json, true); //String To JSON Format
//var_dump($data); //Testing & Troubleshooting Purposes
$array = json_decode($rawdata, true); // JSON Format to Array
//var_dump($array); //Testing & Troubleshooting Purposes



//====================================
//          JSON Data Format
//====================================
/*
* JSON Data Breakdown
*
* Object        Description        Value       Remarks
*
* Object 1      Trigger Count      Integer     Encrypted with Fernet
*
* Object 2      Increase Interval  Boolean     Encrypted with Fernet
*
* Object 3      Category           String      Encrypted with Fernet (AWD/AMD/PL/OW)
*
* Object 4      Data/Information   String      Encrypted with Fernet
*
* Object 5      Fernet Key         String      Encrypted with RSA & Encoded in BASE64
*
* Object 6      UUID               String      Encrypted with Fernet
*/

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

$encryptedfernetkey = base64_decode($array[5]);
openssl_private_decrypt($encryptedfernetkey, $fernetkey, $RSA_privatekey);
//========================================
//     Fernet (Symmetric Encryption)
//  Decrypting the rest of the JSON Data
//========================================

require_once("Fernet/Fernet.php"); 
use Fernet\Fernet;

$fernet = new Fernet($fernetkey); //Fernet Key

$data = ""; //Define Data Variable
$trigger_count = $fernet->decode($array[1]);
$trigger = $fernet->decode($array[2]);
$category = $fernet->decode($array[3]);
$data = $fernet->decode($array[4]);
$JwT_Token = $fernet->decode($array[7]);
Verify_Token($JwT_Token);
$UUID = getUUID($JwT_Token);
$ProctorSessionID = getSessionID($JwT_Token);

//=============================================
//             Logging Parameters
//=============================================
date_default_timezone_set('Asia/Singapore');
$date_time = date('d-m-Y H:i:s');
$date = date('d-m-Y');

//===================================================
// Validating specified UUID in `intervals` database
//===================================================
$uuid_exist = false;
$query = new MongoDB\Driver\Query(['uuid' => $UUID]);
$rows = $manager->executeQuery("$dbName.intervals", $query);

foreach ($rows as $row) {
    $uuid_exist = true;
    $interval_data = $row->$category;
    $admin_override = $row->admin_override;
    break;
}

//=============================================
//      Retrieve Defaults for `intervals`
//=============================================
$query = new MongoDB\Driver\Query(['name' => 'intervals']);
$rows = $manager->executeQuery("$dbName.defaults", $query);

foreach ($rows as $row) {
    $AWD_Default = $row->AWD;
    $AMD_Default = $row->AMD;
    $PL_Default = $row->PL;
    $OW_Default = $row->OW;
    $KS_Default = $row->KS;
    break;
}

//========================================================================
// Insert default values if UUID does not exist yet in the interval database
//========================================================================
if (!$uuid_exist) {
    $admin_override_default = 0;
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->insert([
        'uuid' => $UUID,
        'AWD' => $AWD_Default,
        'AMD' => $AMD_Default,
        'PL' => $PL_Default,
        'OW' => $OW_Default,
        'KS' => $KS_Default,
        'admin_override' => $admin_override_default
    ]);
    $manager->executeBulkWrite("$dbName.intervals", $bulk);
    echo "Default Interval Initialized.\n";
}

//========================================================================
// Process Trigger (If $trigger is true/1 and $admin_override is false/0)
//========================================================================
if ($trigger == "True" && $admin_override == 0) {
    if ($interval_data > 60) {
        $interval_data -= 60;
    } elseif ($interval_data > 5 && $interval_data <= 60) {
        $interval_data -= 5;
    } else {
        echo "Interval at minimum value.\n";
        exit;
    }
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['uuid' => $UUID],
        ['$set' => [$category => $interval_data]]
    );
    $manager->executeBulkWrite("$dbName.intervals", $bulk);
    echo "Interval Updated\n";
} elseif ($admin_override == 1) {
    echo "Admin Override activated. Interval Value is currently defined by the Administrator.\n";
}

//========================================================================
//  Renaming $category (To be stored inside `proctoring` database later)
//========================================================================
switch ($category) {
    case "AWD":
        $category = "Active Windows Detection (AWD)";
        break;
    case "AMD":
        $category = "Active Monitor Detection (AMD)";
        break;
    case "PL":
        $category = "Process List (PL)";
        break;
    case "OW":
        $category = "Open Windows (OW)";
        break;
    case "KS":
        $category = "Keystrokes (KS)";
        break;
    case "VM":
    $category = "Virtual Machine Detection (VM)";
    break;
}

//========================================================================
//     Inserting Data into `proctoring` database for viewing/analysis
//========================================================================
$bulk = new MongoDB\Driver\BulkWrite;
$bulk->insert([
    'uuid' => $UUID,
    'trigger_count' => $trigger_count,
    'category' => $category,
    'data' => $data,
    'date_time' => $date_time
]);
$manager->executeBulkWrite("$dbName.Processes", $bulk);
echo "Proctoring Data inserted successfully.\n";

//=============================================
//             Close SQL Connection
//=============================================
// MongoDB connection is closed automatically when the script ends.

function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . date('d-m-Y H:i:s', $date_time->toDateTime()->getTimestamp()) . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}

?>