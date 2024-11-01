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

    $uuid = isset($_POST['uuid']) ? $_POST['uuid'] : null;

    if (!$uuid) {
        http_response_code(400);
        echo json_encode(['error' => 'UUID is required']);
        exit;
    }

    $collectionName = 'cert_data'; // Adjust based on your collection name

    // Fetch the current revoked status
    $query = new MongoDB\Driver\Query(['uuid' => $uuid]);
    $currentStatus = $manager->executeQuery("$dbName.$collectionName", $query)->toArray()[0]->revoked;

    // Toggle the revoked status
    $newRevokedStatus = !$currentStatus;

    // Prepare the update
    $bulkWrite = new BulkWrite();
    $bulkWrite->update(['uuid' => $uuid], ['$set' => ['revoked' => $newRevokedStatus]]);

    // Execute the update
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    echo json_encode(['success' => true, 'newStatus' => $newRevokedStatus]);

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error toggling revoked status: ' . $e->getMessage()]);
    exit;
}
?>
