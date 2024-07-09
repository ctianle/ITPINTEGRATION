<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\ObjectId;

// MongoDB connection string
$mongoConnectionString = "mongodb://myuser:mypassword@db:27017";

try {
    // Create a new MongoDB Manager instance
    $manager = new Manager($mongoConnectionString);

    // Get the SessionId from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;

    
    if (!$studentId) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'StudentId is required']);
        exit;
    }

    // Specify the database and collection
    $dbName = 'rapid';
    $collectionName = 'Students';

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Prepare the filter to match the document to delete
    $filter = ['StudentId' => $studentId];

    // Delete the document from the collection
    $bulkWrite->delete($filter, ['limit' => 1]); // Limit to delete only one document

    // Execute the bulk write operation
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

} catch (Exception $e) {
    // Log any exceptions
    error_log("Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    exit;
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
        alert('Document deleted successfully');

        // Redirect to the sessions page after the user clicks OK on the alert
        window.location.href = '../students.php'; // Replace 'students.php' with the actual URL of your sessions page
    </script>
</body>
</html>
