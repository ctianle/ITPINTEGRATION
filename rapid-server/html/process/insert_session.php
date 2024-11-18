<?php
$allowed_roles = ['admin', 'invigilator'];
// Secure session cookie settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript from accessing the session cookie
    ini_set('session.cookie_secure', 1);    // Ensure cookies are only sent over HTTPS (enable HTTPS)
    ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF with the SameSite cookie attribute
    session_start(); // Start the session if it's not already active
}

// Implement session timeout: 30 minutes of inactivity will log the user out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();     // Unset session variables
    session_destroy();   // Destroy the session
    header("Location: ../index.php");  // Redirect to login page
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();  // Update last activity time

// Check if the user is logged in
if (!isset($_SESSION['UserId'])) {
    header("Location: ../index.php");  // Redirect to login page if not authenticated
    exit;
}

// Check if the user has the required role
if (!in_array($_SESSION['UserType'], $allowed_roles)) {
    header("Location: ../overview.php");  // Redirect to overview page if not authorized
    exit;
}


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
// Inside the main script where $manager is initialized
$manager = new MongoDB\Driver\Manager($mongoConnectionString);

// Specify the database and collection
$collection = "$dbName.Sessions";

// Function for redirection with an alert message
function redirectWithMessage($message, $url = '../sessions.php') {
    // Use HTTP redirection for security
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '$url';</script>";
    exit;
}

// Function to get the next sequence value
function getNextSequenceValue($sequenceName, $manager) {
    global $dbName; // Access the global variable $dbName
    
    $filter = ['_id' => $sequenceName];
    $options = ['projection' => ['sequence_value' => 1]];

    $query = new MongoDB\Driver\Query($filter, $options);
    
    // Use $manager passed as an argument
    $cursor = $manager->executeQuery("$dbName.session_sequence", $query);

    foreach ($cursor as $doc) {
        $sequence_value = $doc->sequence_value;
    }

    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->update(
        ['_id' => $sequenceName],
        ['$set' => ['sequence_value' => $sequence_value + 1]],
        ['upsert' => true]
    );

    // Use $manager passed as an argument
    $manager->executeBulkWrite("$dbName.session_sequence", $bulk);

    return $sequence_value;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$MainInvigilatorId = $_SESSION['UserId'];
    $sessionName = $_POST['session_name'] ?? '';
    $date = $_POST['date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';

    if (!empty($startTime) && !empty($endTime)) {
        $startDateTime = new DateTime("$date $startTime");
        $endDateTime = new DateTime("$date $endTime");

        // Check if end time is in AM when start time is in PM
        if ($startDateTime->format('A') === 'PM' && $endDateTime->format('A') === 'AM') {
            redirectWithMessage('End time cannot be in AM if start time is in PM. Please correct the times.');
        }

        // Calculate duration in minutes
        $duration = ($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60; // Convert seconds to minutes

        // Ensure duration is a positive value
        if ($duration < 0) {
            redirectWithMessage('End time must be after start time. Please correct the times.');
        }
    } else {
        redirectWithMessage('Start time and end time must be provided.');
    }

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