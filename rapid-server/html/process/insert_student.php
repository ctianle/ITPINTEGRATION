<?php
session_start();

use MongoDB\Driver\BulkWrite;
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
$bulk = new MongoDB\Driver\BulkWrite;
$collectionName = "Students";

// Function to send JSON response
function sendJsonResponse($status, $message) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    // Retrieve session ID from POST data or query string
    $sessionId = isset($_POST['sessionId']) ? intval($_POST['sessionId']) : intval($_GET['sessionId']);
    
    $file = $_FILES['file']['tmp_name'];

    // Validate the file
    if (!is_uploaded_file($file)) {
        sendJsonResponse('error', 'Invalid file!');
    }

    // Open the file in read mode
    $handle = fopen($file, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Get the first row as the header

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($header) !== count($data)) {
                sendJsonResponse('error', 'Error: Data format mismatch!');
            }

            // Generate a unique _id
            $student = [
                '_id' => new ObjectId(), 
                'StudentId' => (int)$data[0], 
                'StudentName' => $data[1], 
                'Email' => $data[2], 
                'SessionId' => $sessionId
            ];

            $bulk->insert($student); // Queue the insert operation
        }

        // Execute the bulk write operation
        $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        try {
            $result = $manager->executeBulkWrite("$dbName.$collectionName", $bulk, $writeConcern);
            $insertedCount = $result->getInsertedCount();
            sendJsonResponse('success', "CSV file uploaded successfully! Inserted $insertedCount documents.");
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            sendJsonResponse('error', 'Error inserting data: ' . $e->getMessage());
        }

        fclose($handle); // Close the file handle
    } else {
        sendJsonResponse('error', 'Error opening the file!');
    }
} else {
    sendJsonResponse('error', 'Invalid file or no file uploaded!');
}
?>
