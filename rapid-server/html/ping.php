<?php

//=============================================
// MongoDB Connection & Credentials Set Up
//=============================================

$manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@192.168.18.2:27017");

////////////////////////////////////////////////////////////////////////////
// Display Interval Value via Specified UUID & Category (GET METHOD)
////////////////////////////////////////////////////////////////////////////

if (isset($_GET["uuid"])) {

    $UUID = $_GET["uuid"];

    ////////////////////////////////////////////////////////////////////////////

    // Verify if a record for the specified UUID exist in MongoDB Collection "ping"
    ////////////////////////////////////////////////////////////////////////////

    $filter = ['uuid' => $UUID];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $manager->executeQuery('rapid.ping', $query);

    $document = current($cursor->toArray());

    if ($document) {
        echo "Ping Received From " . htmlspecialchars($UUID);
    } else {
        $date_time = date('d-m-Y H:i:s');
        $document = ['uuid' => $UUID, 'last_connect' => $date_time];
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->insert($document);
        $manager->executeBulkWrite('rapid.ping', $bulk);
        echo "New Connection: " . htmlspecialchars($UUID) . "\n";
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
