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

    $bulkWrite = new BulkWrite();

    // Prepare the delete
    $filter = ['uuid' => $uuid];
    $bulkWrite->delete($filter, ['limit' => 1]);

    // Execute the delete
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error deleting entry: ' . $e->getMessage()]);
    exit;
}
?>
