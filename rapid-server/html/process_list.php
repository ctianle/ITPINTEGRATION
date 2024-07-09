<?php

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

$json = file_get_contents('php://input');
$rawdata = json_decode($json, true);

//====================================
// Decrypting Fernet Key (if needed)
//====================================

// Assuming decryption logic here if Fernet key is used

//=============================================
// Inserting Data into MongoDB Collection "proctoring"
//=============================================

$data = $rawdata[4];
$UUID = $rawdata[6];

$date_time = date('d-m-Y H:i:s');

$category = $rawdata[3];

// Assuming data decryption if needed

$data_array = [];
foreach ($data as $raw_data) {
    // Decrypt $raw_data if needed
    $data_inside_list = $raw_data;
    $data_array[] = $data_inside_list;
}

$data_string = implode(", ", $data_array);

$document = [
    'uuid' => $UUID,
    'trigger_count' => $rawdata[1],
    'category' => $category,
    'data' => $data_string,
    'date_time' => $date_time,
];

$bulk = new MongoDB\Driver\BulkWrite();
$bulk->insert($document);
$manager->executeBulkWrite("$dbName.proctoring", $bulk);

echo "Proctoring Data inserted successfully.\n";

function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . $date_time . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}
?>
