<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;

$mongoConnectionString = "mongodb://myuser:mypassword@db:27017";

try {
    $manager = new Manager($mongoConnectionString);

    $postData = file_get_contents('php://input');
    $formData = json_decode($postData, true);

    if (!$formData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data received']);
        exit;
    }

    $dbName = 'rapid';
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
