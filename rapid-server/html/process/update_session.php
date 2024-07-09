<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Exception\Exception as MongoDBException;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection string
$mongoConnectionString = "mongodb://$db_user:$db_password@db:27017";

try {
    // Create a new MongoDB Manager instance
    $manager = new Manager($mongoConnectionString);

    // Ensure SessionId and SessionName are set in the $_GET array
    if (!isset($_GET['SessionId']) || !isset($_GET['SessionName'])) {
        throw new Exception('Missing required fields in the input data');
    }

    // Ensure SessionId is treated as a string if necessary
    $sessionId = isset($_GET['SessionId']) ? (int)$_GET['SessionId'] : null;


    // Specify the database and collection
    $collectionName = 'Sessions';

    // Prepare filter and update data
    $filter = ['SessionId' => $sessionId];
    $updateData = ['$set' => ['SessionName' => $_GET['SessionName']]];

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Update the document in the MongoDB collection
    $bulkWrite->update($filter, $updateData, ['multi' => false, 'upsert' => false]);
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);
    
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
    <title>Delete Success</title>
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
