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
$manager = new MongoDB\Driver\Manager($mongoConnectionString);

// Specify the database and collection
$collection = "$dbName.Sessions";

function redirectWithMessage($message, $url = '../sessions.php') {
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '$url';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sessionId = filter_var($_POST['session_id'], FILTER_VALIDATE_INT); // Ensure session ID is a valid integer
    $sessionName = filter_var($_POST['session_name'] ?? '', FILTER_SANITIZE_STRING);
    $date = filter_var($_POST['date'] ?? '', FILTER_SANITIZE_STRING);
    $startTime = filter_var($_POST['start_time'] ?? '', FILTER_SANITIZE_STRING);
    $endTime = filter_var($_POST['end_time'] ?? '', FILTER_SANITIZE_STRING);
    $blacklist = filter_var($_POST['blacklist'] ?? '', FILTER_SANITIZE_STRING);
    $whitelist = filter_var($_POST['whitelist'] ?? '', FILTER_SANITIZE_STRING);
    
    // Validate input
    if (empty($sessionId) || empty($sessionName) || empty($date) || empty($startTime) || empty($endTime)) {
        redirectWithMessage('All fields must be filled out.');
    }

    // Validate date format
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/'; // YYYY-MM-DD format
    if (!preg_match($datePattern, $date)) {
        redirectWithMessage('Invalid date format. Use YYYY-MM-DD.');
    }

    // Validate time format
    $timePattern = '/^\d{2}:\d{2}$/'; // HH:MM format
    if (!preg_match($timePattern, $startTime) || !preg_match($timePattern, $endTime)) {
        redirectWithMessage('Invalid time format. Use HH:MM.');
    }
    
    // Create DateTime objects for validation
    $startDateTime = DateTime::createFromFormat('Y-m-d H:i', "$date $startTime");
    $endDateTime = DateTime::createFromFormat('Y-m-d H:i', "$date $endTime");

    // Check if DateTime objects were created successfully
    if (!$startDateTime || !$endDateTime) {
        redirectWithMessage('Invalid date or time provided.');
    }

    // Check if end time is after start time
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
