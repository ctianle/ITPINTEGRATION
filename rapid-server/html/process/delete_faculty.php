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

    $userId = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'UserId is required']);
        exit;
    }

    $collectionName = 'Users';

    $bulkWrite = new BulkWrite();

    $filter = ['UserId' => $userId];

    $bulkWrite->delete($filter, ['limit' => 1]);

    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error deleting user: ' . $e->getMessage()]);
    exit;
}
?>
