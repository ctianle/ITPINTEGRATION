<?php
$allowed_roles = ['admin', 'invigilator'];
include('../auth_check.php');

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

    // Get the SessionId from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = isset($_GET['id']) ? $_GET['id'] : null;

    // Validate and sanitize the SessionId
    if (!$sessionId) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'SessionId is required']);
        exit;
    }

    // Ensure SessionId is an integer
    if (!filter_var($sessionId, FILTER_VALIDATE_INT)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid SessionId format. It must be an integer.']);
        exit;
    }

    // Convert to integer (safe since we validated it)
    $sessionId = (int)$sessionId;

    // Specify the database and collection
    $collectionName = 'Sessions';

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Prepare the filter to match the document to delete
    $filter = ['SessionId' => $sessionId];

    // Delete the document from the collection
    $bulkWrite->delete($filter, ['limit' => 1]); // Limit to delete only one document

    // Execute the bulk write operation
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

} catch (Exception $e) {
    // Log any exceptions
    error_log("Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'An unexpected error occurred.']);
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
        window.location.href = '../sessions.php'; // Replace 'sessions.php' with the actual URL of your sessions page
    </script>
</body>
</html>
