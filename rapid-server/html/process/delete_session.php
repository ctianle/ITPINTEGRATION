<?php
$allowed_roles = ['admin', 'invigilator'];

// Secure session cookie settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript from accessing the session cookie
    ini_set('session.cookie_secure', 1);    // Ensure cookies are only sent over HTTPS
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

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\WriteConcern;

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
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = isset($data['sessionId']) ? $data['sessionId'] : null;

    // Validate and sanitize the SessionId
    if (!$sessionId || !filter_var($sessionId, FILTER_VALIDATE_INT)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid SessionId. It must be a valid integer.']);
        exit;
    }

    // Specify the database and collection
    $collectionName = 'Sessions';

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Prepare the filter to match the document to delete
    $filter = ['SessionId' => (int)$sessionId];

    // Delete the document from the collection
    $bulkWrite->delete($filter, ['limit' => 1]); // Limit to delete only one document

    // Execute the bulk write operation
    $result = $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    // Check if a document was deleted
    if ($result->getDeletedCount() === 0) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'No document found matching the criteria.']);
        exit;
    }

    echo json_encode(['message' => 'Document deleted successfully.']);
} catch (Exception $e) {
    // Log any exceptions
    error_log("Exception: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'An unexpected error occurred.']);
    exit;
}
?>
