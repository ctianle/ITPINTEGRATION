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
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Query;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// Connect to MongoDB
$manager = new Manager("mongodb://$db_user:$db_password@db:27017");

// Specify the database and collection
$collectionName = "Users";

// Function to alert and redirect
function alertAndRedirect($message) {
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '../faculty.php';</script>";
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Check if all required form fields are present
    if (isset($_POST['user_id'], $_POST['user_type'], $_POST['name'], $_POST['email'], $_POST['password'])) {
        
        // Collect and input validate
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $user_type = sanitizeMongoInput($_POST['user_type']);
        $name = sanitizeMongoInput($_POST['name']); 
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL); 
        $plainPassword = $_POST['password']; 
        

        // Validate user_id
        if ($user_id === false || $user_id <= 0) {
            alertAndRedirect('Invalid User ID! It must be a positive integer.');
        }

        // Validate user_type
        if (empty($user_type) || strlen($user_type) > 50) {
            alertAndRedirect('Invalid User Type! It must be a non-empty string with a maximum of 50 characters.');
        }

        // Validate name
        if (empty($name) || strlen($name) > 100) {
            alertAndRedirect('Invalid Name! It must be a non-empty string with a maximum of 100 characters.');
        }
        
        // Validate email
        if (!$email) {
            alertAndRedirect('Invalid email address!');
        }

        // Hash the password using bcrypt with 10 rounds of salting
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        // Check if the User ID already exists
        $filter = ['UserId' => $user_id];
        $query = new Query($filter);
        $existingUser = $manager->executeQuery("$dbName.$collectionName", $query)->toArray();

        if (!empty($existingUser)) {
            alertAndRedirect('User ID is already in use!');
        }

        // Check if the Email already exists
        $filterByEmail = ['Email' => $email];
        $queryByEmail = new Query($filterByEmail);
        $existingUserByEmail = $manager->executeQuery("$dbName.$collectionName", $queryByEmail)->toArray();

        if (!empty($existingUserByEmail)) {
            alertAndRedirect('Email is already in use!');
        }

        // Generate a unique _id and prepare the user data array
        $user = [
            '_id' => new ObjectId(),
            'UserId' => $user_id,
            'UserType' => $user_type,
            'UserName' => $name,
            'Email' => $email,
            'PasswordHash' => $hashedPassword,
            'isActive' => true
        ];

        // Queue the insert operation
        $bulk = new BulkWrite;
        $bulk->insert($user);

        // Execute the bulk write operation
        $writeConcern = new WriteConcern(WriteConcern::MAJORITY, 1000);

        try {
            $result = $manager->executeBulkWrite("$dbName." . $collectionName, $bulk, $writeConcern);
            $insertedCount = $result->getInsertedCount();
            alertAndRedirect('Faculty member added successfully! Inserted ' . $insertedCount . ' document.');
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            alertAndRedirect('Error inserting data: ' . $e->getMessage());
        }

    } else {
        alertAndRedirect('All fields are required!');
    }
} else {
    alertAndRedirect('Invalid request method!');
}

function sanitizeMongoInput($input) {
    return str_replace(['$', '{', '}'], ['\$'], $input);
}
?>
