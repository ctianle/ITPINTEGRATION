<?php
session_start();

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Exception\Exception as MongoDBException;

// MongoDB connection string
$mongoConnectionString = "mongodb://myuser:mypassword@db:27017";

try {
    // Create a new MongoDB Manager instance
    $manager = new Manager($mongoConnectionString);

    // Ensure studentId, name, email, and sessionId are set in the $_GET array
    if (!isset($_GET['studentId']) || !isset($_GET['name']) || !isset($_GET['email']) || !isset($_GET['sessionId'])) {
        throw new Exception('Missing required fields in the input data');
    }

	// Retrieve the studentId, name, email, and sessionId from the $_GET array
    $studentId = (int)$_GET['studentId'];
    $name = $_GET['name'];
    $email = $_GET['email'];
    $sessionId = (int)$_GET['sessionId'];

    // Specify the database and collection
    $dbName = 'rapid';
    $collectionName = 'Students';

    // Prepare filter and update data
    $filter = ['StudentId' => $studentId];
    $updateData = ['$set' => [
        'StudentName' => $name,
        'Email' => $email,
        'SessionId' => $sessionId
    ]];

    // Create a new BulkWrite instance
    $bulkWrite = new BulkWrite();

    // Update the document in the MongoDB collection
    $bulkWrite->update($filter, $updateData, ['multi' => false, 'upsert' => false]);
    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

} catch (MongoDBException $e) {
    // Log the MongoDB exception
    error_log("MongoDB Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'MongoDB Exception: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Log any other exception
    error_log("Exception: " . $e->getMessage());
    // Return an error response
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Success</title>
</head>
<body>
    <script>
        // Show an alert
        alert('Student information updated successfully');

        // Redirect to the students page after the user clicks OK on the alert
        window.location.href = '../students.php'; // Replace 'students.php' with the actual URL of your students page
    </script>
</body>
</html>
