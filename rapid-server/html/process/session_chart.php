<?php
$allowed_roles = ['admin', 'invigilator'];
include('../auth_check.php');

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

    // Check if session UserId is set
    if (isset($_SESSION['UserId'])) {
        // Specify the database and collection
        $collectionName = 'Sessions';

        // Retrieve data from MongoDB with filter
        $filter = ['MainInvigilatorId' => $_SESSION['UserId']];
        $options = [];
        $query = new Query($filter, $options);
        $cursor = $manager->executeQuery("$dbName.$collectionName", $query);

        // Initialize counters for different statuses
        $completedCount = 0;
        $plannedCount = 0;
        $ongoingCount = 0;

        // Iterate over the cursor and count sessions based on status
        foreach ($cursor as $document) {
            // Convert MongoDB UTCDateTime to PHP DateTime object
            $startTime = $document->StartTime->toDateTime();

            // Determine session status based on start time
            $now = new DateTime();
            if ($startTime < $now) {
                $completedCount++; // Past sessions
            } elseif ($startTime > $now) {
                $plannedCount++; // Future sessions
            } else {
                $ongoingCount++; // Current sessions
            }
        }

        // Prepare data for chart
        $chartData = [
            'completed' => $completedCount,
            'planned' => $plannedCount,
            'ongoing' => $ongoingCount,
        ];

        // Return the chart data as JSON
        echo json_encode($chartData);
    } else {
        echo json_encode(['error' => 'UserId not set in session.']);
    }
} catch (MongoDBException $e) {
    echo json_encode(['error' => 'Error connecting to MongoDB: ' . $e->getMessage()]);
}
?>
