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

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
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

    // Specify the database and collection
    $collectionName = 'Students';

    // Ensure SessionId is treated as a string if necessary
    $sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : null;

    // Construct query to filter by session_id
    $filter = ['SessionId' => $sessionId];
    $query = new Query($filter);

    // Execute query to retrieve filtered student documents
    $cursor = $manager->executeQuery("$dbName.$collectionName", $query);

    // Prepare an array to hold the student data
    $studentsData = [];

    // Iterate over the cursor and format the data
    foreach ($cursor as $document) {
        $student = [
            '_id' => (string)$document->_id,
            'student_id' => $document->StudentId,
            'name' => $document->StudentName,
            'email' => $document->Email,
            'session_id' => $document->SessionId  // Assuming SessionId is a field in the Students collection
        ];
        $studentsData[] = $student;
    }

    // Return the student data as JSON
    echo json_encode($studentsData);
    
} catch (MongoDBException $e) {
    echo json_encode(['error' => 'Error connecting to MongoDB: ' . $e->getMessage()]);
}
?>
