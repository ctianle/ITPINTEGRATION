<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\Exception\Exception as MongoDBException;

// MongoDB connection string
$mongoConnectionString = "mongodb://myuser:mypassword@db:27017";

try {
    // Create a new MongoDB Manager instance
    $manager = new Manager($mongoConnectionString);

    // Specify the database and collection
    $collectionName = 'Students';

    // Retrieve all student documents from MongoDB
    $query = new Query([]);
    $cursor = $manager->executeQuery("rapid.$collectionName", $query);

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
