<?php

function decodeBase64Utf16($data) {
    // Decode the base64 string
    $decodedData = base64_decode($data);

    // Convert from UTF-16LE to UTF-8
    $utf8Data = mb_convert_encoding($decodedData, 'UTF-8', 'UTF-16LE');

    return $utf8Data;
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

//==========================================================================
// Display Interval Value via Specified UUID & Category (GET METHOD)
//==========================================================================

if (isset($_GET["uuid"]) && isset($_GET["category"])) {

    $uuid = $_GET["uuid"];
    $encodedcategory = $_GET["category"];
    $category = decodeBase64Utf16($encodedcategory);

    // Retrieve Interval Value from MongoDB Collection "intervals"
    $filter = ['uuid' => $uuid];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $manager->executeQuery("$dbName.intervals", $query);

    $document = current($cursor->toArray());

    if ($document) {
        switch ($category) {
            case "AWD":
                $interval_data = $document->AWD;
                break;
            case "AMD":
                $interval_data = $document->AMD;
                break;
            case "PL":
                $interval_data = $document->PL;
                break;
            case "OW":
                $interval_data = $document->OW;
                break;
            default:
                $interval_data = 300; // Default value if category is invalid
        }
        echo $interval_data;
    } else {
        echo 300; // Default value if no data found
    }
} else {
    echo "UUID and category parameters are required.";
}

function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . $date_time . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}
?>
