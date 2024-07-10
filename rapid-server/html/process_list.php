<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\Driver\BulkWrite;

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
        // Token is valid
        return true;

    } catch (Exception $e) {
        // Handle errors
        error_log('JWT decode error: ' . $e->getMessage());
        exit;
    }
}

//=============================================
// MongoDB Connection & Credentials Set Up
//=============================================

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

//====================================
// Decode POST Request (JSON)
//====================================

// Decode POST Request (JSON)
$json = file_get_contents('php://input');
$rawdata = json_decode($json, true);
$array = json_decode($rawdata, true);

//====================================
// Decrypting Fernet Key (if needed)
//====================================

// Fetch the CA key passphrase from environment variable
$ca_key_passphrase = getenv('CA_KEY_PASSPHRASE');
if (!$ca_key_passphrase) {
    error_log('CA key passphrase environment variable is not set.');
}

// Load the CA private key
$ca_private_key = file_get_contents("/var/www/keys/private_rsa.key");
if (!$ca_private_key) {
    error_log('Failed to read CA private key.');
    respond('error', 'Failed to read CA private key.');
}

$RSA_privatekey = openssl_pkey_get_private($ca_private_key, $ca_key_passphrase);
if (!$RSA_privatekey) {
    error_log('Failed to parse CA private key: ' . openssl_error_string());
}
$encryptedfernetkey = base64_decode($array[5]);
openssl_private_decrypt($encryptedfernetkey, $fernetkey, $RSA_privatekey);


//=============================================
// Inserting Data into MongoDB Collection "proctoring"
//=============================================
require_once("Fernet/Fernet.php");
use Fernet\Fernet;

$fernet = new Fernet($fernetkey);
$data = "";
$trigger_count = $fernet->decode($array[1]);
$trigger = $fernet->decode($array[2]);
$category = $fernet->decode($array[3]);
foreach ($array['4'] as $raw_data) {
    $data_inside_list = $fernet->decode($raw_data);
    $data .= $data_inside_list . ", ";
}
$data = substr($data, 0, -2);
$UUID = $fernet->decode($array[6]);
$JwT_Token = $fernet->decode($array[7]);
Verify_Token($JwT_Token);

date_default_timezone_set('Asia/Singapore');
$date_time = date('d-m-Y H:i:s');
$date = date('d-m-Y');

// Validating specified UUID in `intervals` collection
$uuid_exist = false;
$query = new Query(['uuid' => $UUID]);
$cursor = $manager->executeQuery("$dbName.intervals", $query);
$interval_data = null;
$admin_override = 0;

foreach ($cursor as $document) {
    $uuid_exist = true;
    $document = (array) $document;
    switch ($category) {
        case "AWD":
            $interval_data = $document['AWD'];
            break;
        case "AMD":
            $interval_data = $document['AMD'];
            break;
        case "PL":
            $interval_data = $document['PL'];
            break;
        case "OW":
            $interval_data = $document['OW'];
            break;
        case "KS":
            $interval_data = $document['KS'];
            break;
    }
    $admin_override = $document['admin_override'];
}

// Retrieve Defaults for `intervals`
$query = new Query(['name' => 'intervals']);
$cursor = $manager->executeQuery("$dbName.defaults", $query);
$defaults = [];

foreach ($cursor as $document) {
    $defaults = (array) $document;
}

$AWD_Default = $defaults['AWD'];
$AMD_Default = $defaults['AMD'];
$PL_Default = $defaults['PL'];
$OW_Default = $defaults['OW'];
$KS_Default = $defaults['KS'];

// Insert default values if UUID does not exist yet in the interval collection
if (!$uuid_exist) {
    $admin_override_default = 0;
    $bulk = new BulkWrite;
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

// Process Trigger
if ($trigger == "True" && $admin_override == 0) {
    if ($interval_data > 60) {
        $interval_data -= 60;
    } elseif ($interval_data > 5 && $interval_data <= 60) {
        $interval_data -= 5;
    } else {
        echo "Interval at minimum value.\n";
        exit;
    }

    $bulk = new BulkWrite;
    $bulk->update(
        ['uuid' => $UUID],
        ['$set' => [$category => $interval_data]],
        ['multi' => false, 'upsert' => false]
    );
    $manager->executeBulkWrite("$dbName.intervals", $bulk);
    echo "Interval Updated\n";
} elseif ($admin_override == 1) {
    echo "Admin Override activated. Interval Value is currently defined by the Administrator.\n";
}

// Renaming $category
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
}

// Inserting Data into `proctoring` collection
$bulk = new BulkWrite;
$bulk->insert([
    'uuid' => $UUID,
    'trigger_count' => $trigger_count,
    'category' => $category,
    'data' => $data,
    'date_time' => $date_time
]);
$manager->executeBulkWrite("$dbName.proctoring", $bulk);
echo "Proctoring Data inserted successfully.\n";

// Logging Parameters
function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . $date_time . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}
?>