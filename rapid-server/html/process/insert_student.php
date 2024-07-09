<?php
session_start();

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');

// Connect to MongoDB
$manager = new MongoDB\Driver\Manager("mongodb://$db_user:$db_password@db:27017");

// Specify the database and collection
$bulk = new MongoDB\Driver\BulkWrite;
$collectionName = "Students";

function alertAndRedirect($message) {
    echo "<script type='text/javascript'>alert('$message'); window.location.href = '../students.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file']) && isset($_POST['sessionDropdown'])) {
    $file = $_FILES['file']['tmp_name'];
    $sessionId = intval($_POST['sessionDropdown']);

    // Validate the file
    if (!is_uploaded_file($file)) {
        alertAndRedirect('Invalid file!');
    }

    // Open the file in read mode
    $handle = fopen($file, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Get the first row as the header

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($header) !== count($data)) {
                alertAndRedirect('Error: Data format mismatch!');
            }

            // Generate a unique _id
            $student = ['_id' => new ObjectId(), 'StudentId' => (int)$data[0], 'StudentName' => $data[1], 'Email' => $data[2], 'SessionId' => $sessionId];

            $bulk->insert($student); // Queue the insert operation
        }

        // Execute the bulk write operation
        $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        try {
            $result = $manager->executeBulkWrite("$dbName." . $collectionName, $bulk, $writeConcern);
            $insertedCount = $result->getInsertedCount();
            alertAndRedirect('CSV file uploaded successfully! Inserted ' . $insertedCount . ' documents.');
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            alertAndRedirect('Error inserting data: ' . $e->getMessage());
        }

        fclose($handle); // Close the file handle
    } else {
        alertAndRedirect('Error opening the file!');
    }
} else {
    alertAndRedirect('Invalid file or no file uploaded or no session selected!');
}
?>
