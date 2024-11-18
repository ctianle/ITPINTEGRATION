<?php
$allowed_roles = ['admin', 'invigilator'];
include('../auth_check.php');


use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;
use MongoDB\BSON\ObjectId;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// Connect to MongoDB
$manager = new MongoDB\Driver\Manager("mongodb://$db_user:$db_password@db:27017");

// Specify the database and collection
$bulk = new MongoDB\Driver\BulkWrite;
$collectionName = "Students";

// Function to send JSON response
function sendJsonResponse($status, $message) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {

    if (!isset($_POST['sessionId']) || !ctype_digit($_POST['sessionId']) || (int)$_POST['sessionId'] < 0) {
        sendJsonResponse('error', 'Session ID is missing or invalid! It must be a non-negative integer.');
    }
    $sessionId = (int)$_POST['sessionId']; // Valid and safely cast

    $file = $_FILES['file']['tmp_name'];

    // Validate the file
    if (!is_uploaded_file($file)) {
        error_log("Error: Invalid file or not via HTTP POST.");
        sendJsonResponse('error', 'Invalid file!');
    }

    // Check file type to ensure it's a CSV
    $fileType = mime_content_type($file);
    // Allow additional valid CSV MIME types
    $allowedTypes = ['application/csv'];

    if (!in_array($fileType, $allowedTypes)) {
        sendJsonResponse('error', 'Invalid file type! Only CSV files are allowed.');
    }

    // Open the file in read mode
    $handle = fopen($file, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Get the first row as the header

        if (empty($header) || count($header) < 3) {
            sendJsonResponse('error', 'Invalid CSV format! Ensure the file has at least 3 columns.');
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            if (count($header) !== count($data)) {
                error_log("Error: Data format mismatch detected in the CSV row.");
                sendJsonResponse('error', 'Error: Data format mismatch!');
            }

            // Sanitize and validate each field
            $studentId = filter_var($data[0], FILTER_VALIDATE_INT);
            $studentName = htmlspecialchars(trim($data[1]), ENT_QUOTES, 'UTF-8');
            $email = filter_var(trim($data[2]), FILTER_VALIDATE_EMAIL);

            // Validate StudentId
            if ($studentId === false || $studentId <= 0) {
                error_log("Invalid Student ID detected: $data[0]");
                sendJsonResponse('error', 'Invalid Student ID in the CSV. It must be a positive integer.');
            }

            // Validate StudentName
            if (empty($studentName) || strlen($studentName) > 100) {
                error_log("Invalid Student Name detected: $data[1]");
                sendJsonResponse('error', 'Invalid Student Name. It must be non-empty and a maximum of 100 characters.');
            }

            // Validate Email
            if ($email === false) {
                error_log("Invalid Email detected: $data[2]");
                sendJsonResponse('error', 'Invalid email address in the CSV.');
            }

            // Generate a unique _id
            $student = [
                '_id' => new ObjectId(),
                'StudentId' => $studentId,
                'StudentName' => $studentName,
                'Email' => $email,
                'SessionId' => $sessionId
            ];

            $bulk->insert($student); // Queue the insert operation
        }

        // Execute the bulk write operation
        $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
        try {
            $result = $manager->executeBulkWrite("$dbName.$collectionName", $bulk, $writeConcern);
            $insertedCount = $result->getInsertedCount();
            sendJsonResponse('success', "CSV file uploaded successfully! Inserted $insertedCount documents.");
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            sendJsonResponse('error', 'Error inserting data: ' . $e->getMessage());
        }

        fclose($handle); // Close the file handle
    } else {
        sendJsonResponse('error', 'Error opening the file!');
    }
} else {
    sendJsonResponse('error', 'Invalid file or no file uploaded!');
}
?>
