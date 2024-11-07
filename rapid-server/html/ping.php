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

////////////////////////////////////////////////////////////////////////////
// Display Interval Value via Specified UUID & Category (GET METHOD)
////////////////////////////////////////////////////////////////////////////
if (isset($_GET["uuid"]) && !empty($_GET["uuid"])) {

    $UUID = $_GET["uuid"];
    error_log("UUID : $UUID");
    ////////////////////////////////////////////////////////////////////////////

    // Verify if a record for the specified UUID exist in MongoDB Collection "ping"
    ////////////////////////////////////////////////////////////////////////////

    $filter = ['uuid' => $UUID];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $manager->executeQuery("$dbName.ping", $query);

    $document = current($cursor->toArray());

    if ($document) {
        // UUID exists, so update the last_connect field
        $date_time = date('d-m-Y H:i:s');
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(
            ['uuid' => $UUID],              // Filter
            ['$set' => ['last_connect' => $date_time]], // Update operation
            ['upsert' => false]             // Don't create a new document if it doesn't exist
        );
        $manager->executeBulkWrite("$dbName.ping", $bulk);
        echo "Ping Received From " . htmlspecialchars($UUID);
    } else {
        $date_time = date('d-m-Y H:i:s');
        $document = ['uuid' => $UUID, 'last_connect' => $date_time];
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        $manager->executeBulkWrite("$dbName.ping", $bulk);
        echo "New Connection: " . htmlspecialchars($UUID) . "\n";
    }
}
else {
    error_log("UUID is either not set or empty");
}

function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . $date_time . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}
?>