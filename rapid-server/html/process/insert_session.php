<?php
session_start();

use MongoDB\Driver\Exception\BulkWriteException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Connect to MongoDB
$manager = new Manager("mongodb://myuser:mypassword@db:27017");

// Specify the database and collection
$collection = "rapid.Sessions";

// Function for redirection with an alert message
function redirectWithMessage($message, $url = '../sessions.php') {
    // Use HTTP redirection for security
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '$url';</script>";
    exit;
}

// Function to get the next sequence value
function getNextSequenceValue($sequenceName, $manager) {
    $filter = ['_id' => $sequenceName];
    $options = ['projection' => ['sequence_value' => 1]];

    $query = new MongoDB\Driver\Query($filter, $options);
    $cursor = $manager->executeQuery('rapid.session_sequence', $query);

    foreach ($cursor as $doc) {
        $sequence_value = $doc->sequence_value;
    }

    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => $sequenceName],
        ['$set' => ['sequence_value' => $sequence_value + 1]],
        ['upsert' => true]
    );

    $manager->executeBulkWrite('rapid.session_sequence', $bulk);

    return $sequence_value;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$MainInvigilatorId = $_SESSION['UserId'];
    $sessionName = $_POST['session_name'] ?? '';
    $date = $_POST['date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $duration = (int)($_POST['duration'] ?? 0);

    // Validate inputs as needed

    $whitelist = $_POST['whitelist'] ?? [];
    $blacklist = $_POST['blacklist'] ?? [];

    $startDateTime = new DateTime("$date $startTime");
    $endDateTime = new DateTime("$date $endTime");

    // Get the next SessionId
    $sessionId = getNextSequenceValue("sessionId", $manager);

    $session = [
        '_id' => new ObjectId(),
        'SessionId' => $sessionId,
        'MainInvigilatorId' => $MainInvigilatorId,
        'SessionName' => $sessionName,
        'StartTime' => new UTCDateTime($startDateTime->getTimestamp() * 1000),
        'EndTime' => new UTCDateTime($endDateTime->getTimestamp() * 1000),
        'Duration' => $duration,
        'BlacklistedApps' => $blacklist,
        'WhitelistedApps' => $whitelist,
        'CreatedAt' => new UTCDateTime()
    ];

    $bulk = new BulkWrite;
    $bulk->insert($session);

    $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);

    try {
        $result = $manager->executeBulkWrite($collection, $bulk, $writeConcern);
        $insertedCount = $result->getInsertedCount();
        redirectWithMessage('Session created successfully! Inserted ' . $insertedCount . ' documents.');
    } catch (BulkWriteException $e) {
        // Log error for debugging
        redirectWithMessage('Error creating session: ' . $e->getMessage());
    } catch (Exception $e) {
        // Log error for debugging
        redirectWithMessage('Unexpected error: ' . $e->getMessage());
    }
} else {
    redirectWithMessage('Invalid request!');
}

?>
