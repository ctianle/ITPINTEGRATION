<?php
session_start();

use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection string
$mongoConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoConnectionString);

// Specify the database and collection
$collection = "$dbName.Sessions";

function redirectWithMessage($message, $url = '../sessions.php') {
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '$url';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sessionId = $_POST['session_id']; // Get the session ID from the form
    $sessionName = $_POST['session_name'] ?? '';
    $date = $_POST['date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    
    // Validate input
    if (empty($sessionId) || empty($sessionName) || empty($date) || empty($startTime) || empty($endTime)) {
        redirectWithMessage('All fields must be filled out.');
    }
    
    // Create DateTime objects for validation
    $startDateTime = new DateTime("$date $startTime");
    $endDateTime = new DateTime("$date $endTime");
    
    // Check duration
    if ($endDateTime < $startDateTime) {
        redirectWithMessage('End time must be after start time.');
    }
    
    // Calculate duration in minutes
    $duration = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60; // Convert seconds to minutes

    // Prepare the update data
    $updateData = [
        '$set' => [
            'SessionName' => $sessionName,
            'StartTime' => new UTCDateTime($startDateTime->getTimestamp() * 1000),
            'EndTime' => new UTCDateTime($endDateTime->getTimestamp() * 1000),
            'Duration' => (int)$duration,
            'BlacklistedApps' => explode(',', trim($_POST['blacklist'])), // Convert to array
            'WhitelistedApps' => explode(',', trim($_POST['whitelist'])) // Convert to array
        ]
    ];

    // Create the bulk write object
    $bulk = new BulkWrite;
    $bulk->update(
        ['SessionId' => (int)$sessionId], // Ensure sessionId is treated correctly (int)
        $updateData
    );

    try {
        $result = $manager->executeBulkWrite($collection, $bulk);
        redirectWithMessage('Session updated successfully!');
    } catch (BulkWriteException $e) {
        redirectWithMessage('Error updating session: ' . $e->getMessage());
    } catch (Exception $e) {
        redirectWithMessage('Unexpected error: ' . $e->getMessage());
    }
} else {
    redirectWithMessage('Invalid request!');
}
?>
