<?php
// Secure session cookie settings
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript from accessing the session cookie
ini_set('session.cookie_secure', 1);    // Ensure cookies are only sent over HTTPS (ensure HTTPS is used)
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF by enforcing same-site cookie policy

 

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

    // Check if form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve email and password from POST request
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['login_error'] = "Invalid email format.";
            header("Location: ../index.php");
            exit;
        }

        // Validate password (ensure it's not empty and meets basic requirements)
        if (empty($password)) {
            $_SESSION['login_error'] = "Empty Password";
            header("Location: ../index.php");
            exit;
        }
        
        // Create a query to find the user by email and password hash
        $filter = ['Email' => $email];
        $query = new Query($filter);

        // Execute the query
        $cursor = $manager->executeQuery("$dbName.Users", $query);

        // Check if user is found
        $user = current($cursor->toArray());

        if ($user) {

            //Retrieve password hash from database
            $storedPasswordHash = $user->PasswordHash;

            //Verify that password matches the stored hash
            if (password_verify($password, $storedPasswordHash)) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                // Set session variables
                $_SESSION['UserName'] = $user->UserName;
                $_SESSION['UserId'] = $user->UserId;
                // Set Session Role
                $_SESSION['UserType'] = $user->UserType;
                
                // Redirect to the overview page
                header("Location: ../overview.php");
                exit;
            } else {
                // Invalid login, redirect back to index with an error message
                $_SESSION['login_error'] = "Invalid email or password.";
                header("Location: ../index.php");
                exit;
            }
            
        } else {
            // User not found, redirect back to the index page with an error message
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: ../index.php");
            exit;
        }
    }
} catch (MongoDBException $e) {
    // Handle any MongoDB exceptions
    $_SESSION['login_error'] = "Error connecting to MongoDB: " . $e->getMessage();
    header("Location: ../index.php");
    exit;
}
?>
