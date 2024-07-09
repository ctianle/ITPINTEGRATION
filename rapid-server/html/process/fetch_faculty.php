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
    $collectionName = 'Users';

    // Retrieve all user documents from MongoDB
    $query = new Query([]);
    $cursor = $manager->executeQuery("rapid.$collectionName", $query);

    // Prepare an array to hold the user data
    $usersData = [];

    // Iterate over the cursor and format the data
	foreach ($cursor as $document) {
	    // Log each document for debugging
	    error_log("Document retrieved: " . json_encode($document));

	    $user = [
		'_id' => (string)$document->_id,
		'UserType' => $document->UserType,
		'UserName' => $document->UserName,
		'Email' => $document->Email,
		'PasswordHash' => $document->PasswordHash,
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
