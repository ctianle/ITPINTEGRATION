<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve form input
        $email = $_POST['email'];
        $plainPassword = $_POST['password'];

        // Validate inputs (basic example, you can expand this)
        if (empty($email) || empty($plainPassword)) {
            $_SESSION['register_error'] = "Email and password are required.";
            header("Location: ../register.php");
            exit;
        }

        // Hash the password using bcrypt with 10 rounds of salting
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 10]);

        // Find the highest current UserId
        $query = new Query([], ['sort' => ['UserId' => -1], 'limit' => 1]); // Sort by UserId descending and limit to 1
        $cursor = $manager->executeQuery("$dbName.Users", $query);
        $userWithHighestId = current($cursor->toArray());

        if ($userWithHighestId) {
            $newUserId = $userWithHighestId->UserId + 1;
        } else {
            $newUserId = 1;  // Start with 1 if there are no users
        }

        // Debugging output for checking UserId generation
        echo "Generated new UserId: " . $newUserId . "<br>";

        // Prepare the user document
        $bulk = new BulkWrite;
        $userDocument = [
            'Email' => $email,
            'PasswordHash' => $hashedPassword,
            'UserName' => 'DanzelTest',  
            'UserType' => 'invigilator',  
            'UserId' => $newUserId,  
            'isActive' => true,  
        ];

        // Insert new user into the 'Users' collection
        $bulk->insert($userDocument);
        
        // Execute the bulk write
        $result = $manager->executeBulkWrite("$dbName.Users", $bulk);

        // Check if the write was successful
        if ($result->getInsertedCount() === 1) {
            // Debugging output to confirm successful insertion
            echo "User successfully inserted.<br>";
            
            // Redirect to login or success page after registration
            header("Location: ../index.php");
            exit;
        } else {
            // Debugging output if the user insertion failed
            echo "Failed to insert user into the database.<br>";
            $_SESSION['register_error'] = "Failed to insert user into the database.";
            header("Location: ../register.php");
            exit;
        }
    }
} catch (MongoDBException $e) {
    // Debugging output to capture MongoDB exceptions
    echo "Error connecting to MongoDB: " . $e->getMessage() . "<br>";
    $_SESSION['register_error'] = "Error connecting to MongoDB: " . $e->getMessage();
    header("Location: ../register.php");
    exit;
}
?>
