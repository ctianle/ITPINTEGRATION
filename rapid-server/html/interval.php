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

//==========================================================================
// Display Interval Value via Specified UUID & Category (GET METHOD)
//==========================================================================

if (isset($_GET["uuid"]) && isset($_GET["category"])) {

    $uuid = $_GET["uuid"];
    $UUID = mb_convert_encoding(base64_decode($uuid), "UTF-16"); //Base64 (UTF-16LE) Decode
    $UUID = preg_replace('/[[:^print:]]/', '', $UUID); //Removing Non Printable Characters

    $category = $_GET["category"];
    $CATEGORY = mb_convert_encoding(base64_decode($category), "UTF-16LE"); //Base64 (UTF-16LE) Decode
    $CATEGORY = preg_replace('/[[:^print:]]/', '', $CATEGORY); //Removing Non Printable Characters

    //==========================================================================

    // Retrieve Interval Value from MongoDB Collection "intervals"
    //==========================================================================

    $filter = ['uuid' => $UUID];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $manager->executeQuery("$dbName.intervals", $query);

    $document = current($cursor->toArray());

    if ($document) {
        switch ($CATEGORY) {
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
            case "KS":
                $interval_data = $document->KS;
                break;
            default:
                $interval_data = 30; // Default value if category is invalid
        }
        echo $interval_data;
    } else {
        echo 30; // Default value if no data found
    }
}

function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . $date_time . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}
?>