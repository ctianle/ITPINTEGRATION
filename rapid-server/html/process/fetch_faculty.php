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
    $collectionName = 'Users';

    // Retrieve all user documents from MongoDB
    $query = new Query([]);
    $cursor = $manager->executeQuery("$dbName.$collectionName", $query);

    // Prepare an array to hold the user data
    $usersData = [];

    // Iterate over the cursor and format the data
	foreach ($cursor as $document) {

	    $user = [
		'_id' => (string)$document->_id,
		'UserType' => $document->UserType,
		'UserName' => $document->UserName,
		'Email' => $document->Email,
		'UserId' => $document->UserId
	    ];
	    $usersData[] = $user;
	}


    // Return the user data as JSON
    echo json_encode($usersData);
    
} catch (MongoDBException $e) {
    echo json_encode(['error' => 'Error connecting to MongoDB: ' . $e->getMessage()]);
}
?>
