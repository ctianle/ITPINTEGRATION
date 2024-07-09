<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection string
$mongoConnectionString = "mongodb://$db_user:$db_password@db:27017";
try {
    $manager = new Manager($mongoConnectionString);

    $postData = file_get_contents('php://input');
    $formData = json_decode($postData, true);

    if (!$formData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data received']);
        exit;
    }

    $collectionName = 'Users';

    $bulkWrite = new BulkWrite();

    $filter = ['UserId' => (int)$formData['userId']];
    $updateData = [
        '$set' => [
            'UserType' => $formData['userType'],
            'UserName' => $formData['userName'],
            'Email' => $formData['userEmail'],
            'PasswordHash' => $formData['passwordHash']
        ]
    ];

    $bulkWrite->update($filter, $updateData, ['multi' => false, 'upsert' => false]);

    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error updating user: ' . $e->getMessage()]);
    exit;
}
?>
