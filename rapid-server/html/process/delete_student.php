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

    // Get the JSON input from the POST request
    $input = json_decode(file_get_contents('php://input'), true);
    $studentId = isset($input['studentId']) ? $input['studentId'] : null;
    $sessionId = isset($input['sessionId']) ? $input['sessionId'] : null;

    // Validate and sanitize inputs
    if (!isset($studentId) || !ctype_digit((string)$studentId) || (int)$studentId < 0) {
        // ctype_digit ensures it consists of only digits (valid non-negative integer)
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid StudentId. It must be a non-negative integer.']);
        exit;
    }

    if (!isset($sessionId) || !filter_var($sessionId, FILTER_VALIDATE_INT)) {
        // Ensure sessionId is a valid integer
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid SessionId. It must be a valid integer.']);
        exit;
    }

    // Convert validated values
    $studentId = (int)$studentId;
    $sessionId = (int)$sessionId;

    // Specify the database and collection
    $collectionName = 'Students';

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Prepare the filter to match the document to delete
    $filter = [
        'StudentId' => $studentId,
        'SessionId' => $sessionId
    ];

    // Delete the document from the collection
    $bulkWrite->delete($filter, ['limit' => 1]); // Limit to delete only one document

    // Execute the bulk write operation
    $result = $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    if ($result->getDeletedCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'No document found matching the criteria.']);
        exit;
    }

    // Respond with success
    echo json_encode(['message' => 'Document deleted successfully.']);
} catch (Exception $e) {
    // Log any exceptions
    error_log("Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'An unexpected error occurred.']);
    exit;
}
?>
