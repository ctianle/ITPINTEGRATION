<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Exception\Exception as MongoDBException;
use MongoDB\BSON\UTCDateTime;
use DateTime;
use DateTimeZone;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection string
$mongoConnectionString = "mongodb://$db_user:$db_password@db:27017";

try {
    // Create a new MongoDB Manager instance
    $manager = new Manager($mongoConnectionString);

    // Ensure necessary fields are set in the $_GET array
    if (!isset($_GET['SessionId']) || !isset($_GET['SessionName']) || !isset($_GET['Date']) || !isset($_GET['StartTime']) || !isset($_GET['EndTime']) || !isset($_GET['Duration']) || !isset($_GET['Blacklist']) || !isset($_GET['Whitelist'])) {
        throw new Exception('Missing required fields in the input data');
    }

    // Extract and validate fields
    $sessionId = (int)$_GET['SessionId'];
    $sessionName = trim($_GET['SessionName']);
    $date = trim($_GET['Date']);
    $startTime = trim($_GET['StartTime']);
    $endTime = trim($_GET['EndTime']);
    $duration = (int)$_GET['Duration'];

    // Decode blacklist and whitelist arrays
    $blacklist = json_decode($_GET['Blacklist'], true);
    $whitelist = json_decode($_GET['Whitelist'], true);

    // Validate and format date and time
    $timezone = new DateTimeZone('Asia/Singapore'); // GMT+8
    $startDateTime = new DateTime("$date $startTime", $timezone);
    $endDateTime = new DateTime("$date $endTime", $timezone);

    // Convert DateTime to UTC for MongoDB
    $utcTimezone = new DateTimeZone('UTC');
    $startDateTime->setTimezone($utcTimezone);
    $endDateTime->setTimezone($utcTimezone);

    // Prepare filter and update data
    $filter = ['SessionId' => $sessionId];
    $updateData = [
        '$set' => [
            'SessionName' => $sessionName,
            'StartTime' => new UTCDateTime($startDateTime->getTimestamp() * 1000),
            'EndTime' => new UTCDateTime($endDateTime->getTimestamp() * 1000),
            'Duration' => $duration,
            'BlacklistedApps' => $blacklist,
            'WhitelistedApps' => $whitelist,
            'Date' => $date // If you want to store the date separately
        ]
    ];

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Update the document in the MongoDB collection
    $bulkWrite->update($filter, $updateData, ['multi' => false, 'upsert' => false]);
    $manager->executeBulkWrite("$dbName.Sessions", $bulkWrite);
    
} catch (MongoDBException $e) {
    // Log the MongoDB exception
    error_log("MongoDB Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'MongoDB Exception: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Log any other exception
    error_log("Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Success</title>
</head>
<body>
    <script>
      // Show an alert
        alert('Session updated successfully');

        // Redirect to the sessions page after the user clicks OK on the alert
        window.location.href = '../sessions.php'; // Replace 'sessions.php' with the actual URL of your sessions page
    </script>
</body>
</html>
