<?php

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

$manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@192.168.18.2:27017");

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

//Decoding Fernet Key (BASE64) & Decrypting Fernet Key with our generated Private Key (RSA)
$RSA_privatekey = file_get_contents("RSA/private_rsa.key");
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
$UUID = $fernet->decode($array[6]);

//=============================================
//             Logging Parameters
//=============================================
date_default_timezone_set('Asia/Singapore');
$date_time = new UTCDateTime((new DateTime())->getTimestamp() * 1000);
$date = date('d-m-Y');

//===================================================
// Validating specified UUID in `intervals` database
//===================================================
$uuid_exist = false;
$query = new MongoDB\Driver\Query(['uuid' => $UUID]);
$rows = $manager->executeQuery('rapid.intervals', $query);

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
$rows = $manager->executeQuery('rapid.defaults', $query);

foreach ($rows as $row) {
    $AWD_Default = $row->AWD;
    $AMD_Default = $row->AMD;
    $PL_Default = $row->PL;
    $OW_Default = $row->OW;
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
        'admin_override' => $admin_override_default
    ]);
    $manager->executeBulkWrite('rapid.intervals', $bulk);
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
    $manager->executeBulkWrite('rapid.intervals', $bulk);
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
$manager->executeBulkWrite('rapid.proctoring', $bulk);
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
