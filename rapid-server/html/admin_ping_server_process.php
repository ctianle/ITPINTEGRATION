<?php

///////////////////////////////////////////////
// MongoDB Connection & Credentials Setup
///////////////////////////////////////////////
// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

///////////////////////////////////////////////
// Logging Parameters
///////////////////////////////////////////////
date_default_timezone_set('Asia/Singapore');
$date_time = date('d-m-Y H:i:s');
$date = date('d-m-Y');

///////////////////////////////////////////////
// Process MongoDB Data
///////////////////////////////////////////////
$query = new MongoDB\Driver\Query([]);
$rows = $manager->executeQuery("$dbName.ping", $query);

foreach ($rows as $row) {
    $last_connect_time = strtotime($row->last_connect);
    $now_time = strtotime($date_time);
    $time_difference = $now_time - $last_connect_time;

    $logfilelocation = "/var/logs/myapp/Heartbeat/" . $row->uuid . ".log";

    if ($time_difference >= 10 && $time_difference < 30) {
        $file = escapeshellarg($logfilelocation);
        $lastline = `tail -n 1 $file`;
        $compare = mb_substr($lastline, 20);
        $forcompare = $row->uuid . " is experiencing connectivity issues.\n";

        if ($compare != $forcompare) {
            $heartbeat_logs = $date_time . " " . $row->uuid . " is experiencing connectivity issues.\n";
            error_log(print_r($heartbeat_logs, true), 3, $logfilelocation);
        }
    } elseif ($time_difference < 10) {
        $file = escapeshellarg($logfilelocation);
        $lastline = `tail -n 1 $file`;
        $compare = mb_substr($lastline, 20);
        $forcompare = $row->uuid . " has initiated connection.\n";

        if ($compare != $forcompare) {
            $heartbeat_logs = $date_time . " " . $row->uuid . " has initiated connection.\n";
            error_log(print_r($heartbeat_logs, true), 3, $logfilelocation);
        }
    } else {
        $file = escapeshellarg($logfilelocation);
        $lastline = `tail -n 1 $file`;
        $compare = mb_substr($lastline, 20);
        $forcompare = $row->uuid . " has been disconnected.\n";

        if ($compare != $forcompare) {
            $heartbeat_logs = $date_time . " " . $row->uuid . " has been disconnected.\n";
            error_log(print_r($heartbeat_logs, true), 3, $logfilelocation);
        }
    }
}

///////////////////////////////////////////////
// Close MongoDB Connection
///////////////////////////////////////////////
$manager = null;

function logError($error) {
    global $date_time;
    $logFile = '/var/logs/myapp/system_error.log';
    $error_message = "\n" . $date_time . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
    echo "An Error occurred.\n";
}

?>
