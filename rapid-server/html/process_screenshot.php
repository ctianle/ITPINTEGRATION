<?php

//====================================
//     Troubleshooting Parameters
//====================================
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

//=============================================
//     SQL Connection & Credentials Set Up
//=============================================
$servername = getenv('DB_SERVERNAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    logError("Connection failed: " . $conn->connect_error);
    die("An error occurred. Check your system_error.log file.");
}
//$conn new mysqli("servername", "db_username", "db_password", "db_name");

//====================================
//     Decode POST Request (JSON)
//====================================

// Takes raw data from the request
$json = file_get_contents('php://input');
//var_dump($json); //Testing & Troubleshooting Purposes

// Converts it into a PHP object
//$rawdata = json_decode($json, true); //String To JSON Format
$rawdata = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true ); // To format it properly due to this new method
//var_dump($rawdata); //Testing & Troubleshooting Purposes
//$array = json_decode($rawdata, true); // JSON Format to Array
//var_dump($array); //Testing & Troubleshooting Purposes

$rawdata = array_values($rawdata);
$data = $rawdata[0];
$UUID = base64_decode($rawdata[1]);

//=============================================
//             Logging Parameters
//=============================================
date_default_timezone_set('Asia/Singapore');
$date_time = date('d-m-Y H:i:s');
$date = date('d-m-Y');

//========================================================================
//     Inserting Data into `proctoring` database for viewing/analysis
//========================================================================

$sql = "INSERT INTO screenshot (uuid, data, date_time) VALUES ('$UUID', '$data', '$date_time')";
if (mysqli_query($conn, $sql)) {
    echo "Screenshot Data inserted successfully.\n";
} else {
    echo "An Error occured.\n";
    $error = "\n" . $date_time . " " . $conn -> error;
    error_log(print_r($error, true), 3, $_SERVER['DOCUMENT_ROOT'] . "/system_error.log");
}

//=============================================
//             Close SQL Connection
//=============================================

$conn->close();

?>