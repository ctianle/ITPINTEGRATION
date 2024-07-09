<?php
// MongoDB connection
$manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@db:27017");

if (isset($_FILES['csv'])) {
    $file = $_FILES['csv']['tmp_name'];
    $csv = array_map('str_getcsv', file($file));
    $header = array_shift($csv); // Remove header row

    // Prepare bulk write
    $bulk = new MongoDB\Driver\BulkWrite;

    foreach ($csv as $row) {
        $processName = $row[0];
        $capturedAt = new DateTime($row[1]);
        $flagDescription = $row[2];

        // Check if the process already exists
        $filter = [
            'ProcessName' => $processName,
            'CapturedAt' => new MongoDB\BSON\UTCDateTime($capturedAt->getTimestamp() * 1000)
        ];
        $query = new MongoDB\Driver\Query($filter);
        $rows = $manager->executeQuery('rapid.StudentProcesses', $query)->toArray();

        if (empty($rows)) {
            // Process does not exist, insert it
            $bulk->insert([
                'ProcessName' => $processName,
                'CapturedAt' => new MongoDB\BSON\UTCDateTime($capturedAt->getTimestamp() * 1000),
                'FlagDescription' => $flagDescription
            ]);
        }
    }

    // Execute bulk write
    try {
        $manager->executeBulkWrite('rapid.StudentProcesses', $bulk);
        header("Location: ../student_overview.php");
        exit;
    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo "Error uploading CSV: " . $e->getMessage();
    }
} else {
    echo "No CSV file uploaded.";
}
?>
