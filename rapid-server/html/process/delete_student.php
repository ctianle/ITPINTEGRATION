<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection string
$mongoConnectionString = "mongodb://$db_user:$db_password@db:27017";

try {
    // Create a new MongoDB Manager instance
    $manager = new Manager($mongoConnectionString);

    // Get the data from the request body
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate studentId and sessionId
    if (!isset($data['studentId']) || !is_numeric($data['studentId']) || (int)$data['studentId'] <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Valid studentId is required and must be a positive number']);
        exit;
    }

    if (!isset($data['sessionId']) || !is_numeric($data['sessionId']) || (int)$data['sessionId'] <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Valid sessionId is required and must be a positive number']);
        exit;
    }

    // Assign validated variables
    $studentId = (int)$data['studentId'];
    $sessionId = (int)$data['sessionId'];
    
    // Specify the database and collection
    $collectionName = 'Students';

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Prepare the filter to match the document to delete
    $filter = [
        'StudentId' => $studentId,
        'SessionId' => $sessionId // Adjusted to include sessionId
    ];

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
