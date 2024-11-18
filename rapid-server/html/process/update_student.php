<?php
$allowed_roles = ['admin', 'invigilator'];
include('../auth_check.php');

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

    // Check if the request is a POST and if the Content-Type is application/json
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false) {
        error_log('Failed request method or content type check');
        throw new Exception('Invalid request method or content type');
    }

    // Get the JSON input
    $inputData = json_decode(file_get_contents('php://input'), true);


    // Check if the inputData is properly parsed
    if ($inputData === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decoding error: ' . json_last_error_msg());
        throw new Exception('Invalid JSON input data');
    }

    // Validate JSON input
    if (!isset($inputData['studentId'], $inputData['name'], $inputData['email'], $inputData['sessionId'])) {
        error_log('Missing required fields in input data');
        throw new Exception('Missing required fields in the input data');
    }

    // Sanitize and validate inputs
    $studentId = filter_var($inputData['studentId'], FILTER_VALIDATE_INT);
    $name = filter_var($inputData['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($inputData['email'], FILTER_VALIDATE_EMAIL);
    $sessionId = filter_var($inputData['sessionId'], FILTER_VALIDATE_INT);

    if ($studentId === false || $sessionId === false) {
        error_log('Invalid studentId or sessionId');
        throw new Exception('Invalid studentId or sessionId. They must be valid integers.');
    }
    if ($name === false || empty($name)) {
        error_log('Invalid or empty name provided');
        throw new Exception('Invalid or empty name provided.');
    }
    if ($email === false) {
        error_log('Invalid email format');
        throw new Exception('Invalid email format.');
    }

    // Specify the database and collection
    $collectionName = 'Students';

    // Prepare filter and update data
    $filter = ['StudentId' => $studentId];
    $updateData = ['$set' => [
        'StudentName' => $name,
        'Email' => $email,
        'SessionId' => $sessionId
    ]];

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Update the document in the MongoDB collection
    $bulkWrite->update($filter, $updateData, ['multi' => false, 'upsert' => false]);
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    // Return a success response in JSON format
    http_response_code(200); // OK
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Student information updated successfully']);
    exit; // Ensure no further output is sent

} catch (MongoDBException $e) {
    // Log the MongoDB exception
    error_log("MongoDB Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'MongoDB Exception: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    // Log any other exception
    error_log("Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
    exit;
}
