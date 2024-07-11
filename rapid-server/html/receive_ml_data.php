<?php
// c2_server.php

// Set the default timezone to Asia/Singapore
date_default_timezone_set('Asia/Singapore');

// Include MongoDB library
require 'vendor/autoload.php';

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\Command;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new Manager($mongoDBConnectionString);

// Log error function
function logError($error) {
    $logFile = '/var/logs/myapp/php_errors.log';
    $error_message = "\n" . date('d-m-Y H:i:s') . " " . $error;
    error_log(print_r($error_message, true), 3, $logFile);
}

// Handle POST request to receive data from PowerShell script
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log request method
    logError('Handling POST request');

    // Get the raw POST data
    $postData = file_get_contents('php://input');
    logError('Raw POST data: ' . $postData);

    // Decode JSON data
    $data = json_decode($postData, true);

    // Check if data is valid JSON
    if (json_last_error() === JSON_ERROR_NONE) {
        logError('JSON data decoded successfully');

        // Prepare data for MongoDB
        $data['timestamp'] = new UTCDateTime((new DateTime())->getTimestamp() * 1000);

        // Insert the received data into MongoDB
        try {
            $bulk = new BulkWrite();
            $bulk->insert($data);
            $result = $manager->executeBulkWrite("$dbName.machine_learning_data", $bulk);

            if ($result->getInsertedCount() === 1) {
                logError('Data inserted into MongoDB successfully');
            } else {
                logError('Failed to insert data into MongoDB');
            }

            // Send response back to the PowerShell script
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Data received successfully']);
        } catch (MongoDB\Driver\Exception\Exception $e) {
            logError('MongoDB Exception: ' . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert data into MongoDB']);
        }
    } else {
        // Invalid JSON received
        logError('Invalid JSON data: ' . json_last_error_msg());
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    }
    exit;
}

// Handle GET request to display the stored data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Log request method
    logError('Handling GET request');

    // Fetch data from MongoDB
    try {
        $query = new Query([], ['sort' => ['timestamp' => -1]]);
        $cursor = $manager->executeQuery("$dbName.machine_learning_data", $query);

        // Display the data
        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Received Data</title>';
        echo '<style>';
        echo 'table { width: 100%; border-collapse: collapse; }';
        echo 'th, td { padding: 8px; border: 1px solid #ddd; }';
        echo 'th { background-color: #f4f4f4; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h1>Received Data</h1>';
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>Type</th><th>Content</th><th>Timestamp</th></tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($cursor as $entry) {
            if ($entry->timestamp instanceof UTCDateTime) {
                $timestamp = $entry->timestamp->toDateTime()->setTimezone(new DateTimeZone('Asia/Singapore'))->format('Y-m-d H:i:s');
            } else {
                $timestamp = htmlspecialchars($entry->timestamp); // Assume it's already a formatted string if not a UTCDateTime
            }
            echo '<tr>';
            echo '<td>' . htmlspecialchars($entry->type) . '</td>';
            echo '<td>' . htmlspecialchars($entry->content) . '</td>';
            echo '<td>' . htmlspecialchars($timestamp) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
    } catch (MongoDB\Driver\Exception\Exception $e) {
        logError('MongoDB Exception: ' . $e->getMessage());
        echo 'Failed to retrieve data.';
    }
}
?>
