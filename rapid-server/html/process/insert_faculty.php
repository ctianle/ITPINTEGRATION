<?php
//session_start();

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
$manager = new MongoDB\Driver\Manager("mongodb://$db_user:$db_password@db:27017");

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
    if (isset($_POST['user_id'], $_POST['user_type'], $_POST['name'], $_POST['email'], $_POST['password_hash'])) {
        // Sanitize and collect the form data
        $user_id = (int) $_POST['user_id'];
        $user_type = htmlspecialchars($_POST['user_type'], ENT_QUOTES, 'UTF-8');
        $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password_hash'];

        if (!$email) {
            alertAndRedirect('Invalid email address!');
        }

        // Check if the User ID already exists
        $filter = ['UserId' => $user_id];
        $query = new MongoDB\Driver\Query($filter);
        $existingUser = $manager->executeQuery("$dbName.$collectionName", $query)->toArray();

        if (!empty($existingUser)) {
            alertAndRedirect('User ID is already in use!');
        }
        

        // Check if the Email already exists
        $filterByEmail = ['Email' => $email];
        $queryByEmail = new MongoDB\Driver\Query($filterByEmail);
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
            'PasswordHash' => $password 
        ];

        // Queue the insert operation
        $bulk = new BulkWrite;
        $bulk->insert($user);

        // Execute the bulk write operation
        $writeConcern = new WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
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
?>
