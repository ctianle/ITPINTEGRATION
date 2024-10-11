<?php

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

    // Get the student_id from the request (e.g., passed via GET or POST)
    $student_id = $_GET['student_id'];  // Assuming it's passed via the URL or form

    // MongoDB query to get the student document based on StudentId
    $filter = ['StudentId' => (int) $student_id];  // Assuming StudentId is stored as an integer
    $query = new Query($filter);
    $cursor = $manager->executeQuery("$dbName.$collectionName", $query);

    // Fetch the student document (assuming StudentId is unique, so we only get one result)
    $studentName = "Unknown";  // Default if not found

    foreach ($cursor as $document) {
        // Extract the student's name
        if (isset($document->StudentName)) {
            $studentName = $document->StudentName;
            $studentID = $document->StudentId;
        }
    }
    
    // Echo the student's name in the HTML
    echo "<span class='activity-title'>{$studentID} {$studentName}'s Activity</span>";

} catch (MongoDBException $e) {
    echo json_encode(['error' => 'Error connecting to MongoDB: ' . $e->getMessage()]);
}
?>
