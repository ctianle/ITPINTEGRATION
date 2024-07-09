<?php

use MongoDB\Client as MongoDBClient;
use MongoDB\BSON\UTCDateTime;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// Querying Data from `proctoring` collection
$filter = [];
$options = [
    'sort' => ['date_time' => -1], // Sort by date_time descending (latest first)
];

$query = new MongoDB\Driver\Query($filter, $options);
$cursor = $manager->executeQuery("$dbName.proctoring", $query);

// Displaying Results
echo "<h1>Proctoring Data</h1>";
echo "<table border='1'>";
echo "<tr><th>UUID</th><th>Trigger Count</th><th>Category</th><th>Data</th><th>Date/Time</th></tr>";

foreach ($cursor as $document) {
    echo "<tr>";
    echo "<td>" . $document->uuid . "</td>";
    echo "<td>" . $document->trigger_count . "</td>";
    echo "<td>" . $document->category . "</td>";
    echo "<td>" . $document->data . "</td>";
    
    // Convert UTCDateTime to DateTime for display
    $date_time = $document->date_time instanceof UTCDateTime ?
        $document->date_time->toDateTime()->format('d-m-Y H:i:s') :
        $document->date_time; // fallback if date_time is not UTCDateTime

    echo "<td>" . $date_time . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
