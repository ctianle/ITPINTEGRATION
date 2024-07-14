<?php
session_start();

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

        // Prepare an array to hold the session data
        $sessionData = [];

        //Set Timezone to SG
        $gmtPlus8Timezone = new DateTimeZone('Asia/Singapore'); // GMT+8 time zone

        // Iterate over the cursor and format the data
        foreach ($cursor as $document) {
            // Convert MongoDB UTCDateTime to PHP DateTime object
            $startTimeUTC = $document->StartTime->toDateTime();
            $endTimeUTC = $document->EndTime->toDateTime();

            // Convert UTC DateTime to GMT+8
            $startTimeGMTPlus8 = clone $startTimeUTC; // Clone to avoid modifying the original
            $endTimeGMTPlus8 = clone $endTimeUTC; // Clone to avoid modifying the original
            $startTimeGMTPlus8->setTimezone($gmtPlus8Timezone);
            $endTimeGMTPlus8->setTimezone($gmtPlus8Timezone);

            // Determine session status based on start and end time
            $status = '';
            $now = new DateTime('now', $gmtPlus8Timezone); // Current time in GMT+8

            if ($endTimeGMTPlus8 < $now) {
                $status = 'complete'; // Past sessions
            } elseif ($startTimeGMTPlus8 > $now) {
                $status = 'planned'; // Future sessions
            } else {
                $status = 'ongoing'; // Current sessions
            }

            // Only include sessions with status 'complete'
            if ($status === 'complete') {
                $session = [
                    '_id' => (string) $document->_id,
                    'SessionId' => $document->SessionId,
                    'SessionName' => $document->SessionName,
                    'StartTime' => $startTimeGMTPlus8->format('Y-m-d H:i:s'), // Convert to GMT+8 formatted string
                    'EndTime' => $endTimeGMTPlus8->format('Y-m-d H:i:s'), // Convert to GMT+8 formatted string
                    'Duration' => $document->Duration,
                    'Status' => $status,
                    'BlacklistedApps' => $document->BlacklistedApps,
                    'WhitelistedApps' => $document->WhitelistedApps
                ];

                $sessionData[] = $session;
            }
        }

        // Return the session data as JSON
        echo json_encode($sessionData);
    } else {
        echo json_encode(['error' => 'UserId not set in session.']);
    }
} catch (MongoDBException $e) {
    echo json_encode(['error' => 'Error connecting to MongoDB: ' . $e->getMessage()]);
}
?>