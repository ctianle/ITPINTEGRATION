<?php
 
$allowed_roles = ['admin'];
include('auth_check.php');
require 'vendor/autoload.php';

use MongoDB\Client as MongoDBClient;


// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// Check if uuid, certificate, and action are sent via POST
if (isset($_POST['uuid']) && isset($_POST['certificate']) && isset($_POST['action'])) {
    $uuid = filter_var($_POST['uuid'], FILTER_SANITIZE_STRING);
    $certificate = filter_var($_POST['certificate'], FILTER_SANITIZE_STRING);
    $action = filter_var($_POST['action'], FILTER_SANITIZE_STRING);

    // Ensure the certificate string is properly formatted
    $certificate = str_replace("\r\n", "\n", $certificate);

    // Define custom UUID pattern
    $uuidPattern = '/^[0-9a-fA-F\-]+$/';  // Example pattern, adjust as needed

    if (!preg_match($uuidPattern, $uuid)) {
        echo "<script>alert('Invalid UUID format.'); window.location.href='admin_cert_data.php';</script>";
        exit;
    }

    if ($action === 'revoke') {
        // Create the query and the update
        $query = new MongoDB\Driver\Query(['uuid' => $uuid, 'certificate' => $certificate]);
        $rows = $manager->executeQuery("$dbName.cert_data", $query)->toArray();

        if (count($rows) > 0) {
            $document = $rows[0];
            $newStatus = !isset($document->revoked) || !$document->revoked;
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->update(
                ['uuid' => $uuid, 'certificate' => $certificate],
                ['$set' => ['revoked' => $newStatus]],
                ['multi' => false, 'upsert' => false]
            );
            $result = $manager->executeBulkWrite("$dbName.cert_data", $bulk);

            if ($result->getModifiedCount() > 0) {
                $status = $newStatus ? "revoked" : "unrevoked";
                echo "<script>alert('Certificate with UUID $uuid has been $status.'); window.location.href='admin_cert_data.php';</script>";
            } else {
                echo "<script>alert('Failed to update the status of the certificate with UUID $uuid.'); window.location.href='admin_cert_data.php';</script>";
            }
        } else {
            echo "<script>alert('Certificate with UUID $uuid not found.'); window.location.href='admin_cert_data.php';</script>";
        }
    } elseif ($action === 'delete') {
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->delete(['uuid' => $uuid, 'certificate' => $certificate], ['limit' => 1]);
        $result = $manager->executeBulkWrite("$dbName.cert_data", $bulk);

        if ($result->getDeletedCount() > 0) {
            echo "<script>alert('Certificate with UUID $uuid has been deleted.'); window.location.href='admin_cert_data.php';</script>";
        } else {
            echo "<script>alert('Failed to delete the certificate with UUID $uuid.'); window.location.href='admin_cert_data.php';</script>";
        }
    }
} else {
    echo "<script>alert('UUID, certificate, or action not provided.'); window.location.href='admin_cert_data.php';</script>";
}
?>