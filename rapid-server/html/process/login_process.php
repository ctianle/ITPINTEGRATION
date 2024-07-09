<?php
session_start();

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
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Create a query to find the user by email and password hash
        $filter = ['Email' => $email, 'PasswordHash' => $password];
        $query = new Query($filter);

        // Execute the query
        $cursor = $manager->executeQuery("$dbName.Users", $query);

        // Check if user is found
        $user = current($cursor->toArray());

        if ($user) {
            // Set session variables
            $_SESSION['UserName'] = $user->UserName;
            $_SESSION['UserId'] = $user->UserId;

            // Redirect to the overview page
            header("Location: ../overview.php");
            exit;
        } else {
            // Invalid login, redirect back to the index page with an error message
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
