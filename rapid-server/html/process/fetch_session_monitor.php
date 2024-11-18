<?php
 

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
        
        // Ensure SessionId is treated as a string if necessary
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;

        // Prepare filter based on session_id
        $filter = [];
        if ($sessionId !== null) {
            $filter = ['SessionId' => (int)$sessionId];
        } else {
            echo json_encode(['error' => 'session_id parameter is required.']);
            exit;
        }
    
        // Add UserId filter if needed
        $filter['MainInvigilatorId'] = $_SESSION['UserId'];

        // Retrieve data from MongoDB with filter
        $query = new Query($filter);
        $cursor = $manager->executeQuery("$dbName.$collectionName", $query);

        // Prepare an array to hold the session data
        $sessionData = [];

        // Iterate over the cursor and format the data
        foreach ($cursor as $document) {
            // Convert MongoDB UTCDateTime to PHP DateTime object
            $startTime = $document->StartTime->toDateTime();

            // Determine session status based on start time
            $status = '';
            $now = new DateTime();
            if ($startTime < $now) {
                $status = 'complete'; // Past sessions
            } elseif ($startTime > $now) {
                $status = 'planned'; // Future sessions
            } else {
                $status = 'ongoing'; // Current sessions
            }
            
            $session = [
                '_id' => (string)$document->_id,
                'SessionId' => $document->SessionId,
                'SessionName' => $document->SessionName,
                'StartTime' => $document->StartTime->toDateTime()->format('Y-m-d H:i:s'), // Convert BSON UTCDateTime to string
                'EndTime' => $document->EndTime->toDateTime()->format('Y-m-d H:i:s'), // Convert BSON UTCDateTime to string
                'Duration' => $document->Duration,
                'Status' => $status,
                'BlacklistedApps' => $document->BlacklistedApps,
                'WhitelistedApps' => $document->WhitelistedApps
            ];
            $sessionData[] = $session;
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
