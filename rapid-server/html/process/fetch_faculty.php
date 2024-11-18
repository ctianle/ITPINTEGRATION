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

    // Specify the database and collection
    $collectionName = 'Users';

    // Retrieve all user documents from MongoDB
    $query = new Query([]);
    $cursor = $manager->executeQuery("$dbName.$collectionName", $query);

    // Prepare an array to hold the user data
    $usersData = [];

    // Iterate over the cursor and format the data
	foreach ($cursor as $document) {

	    $user = [
		'_id' => (string)$document->_id,
		'UserType' => $document->UserType,
		'UserName' => $document->UserName,
		'Email' => $document->Email,
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
