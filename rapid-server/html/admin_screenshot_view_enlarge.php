<?php
$allowed_roles = ['admin'];
include('auth_check.php');
if ($_POST["id"] == NULL) {
    die();
} else {
    $selectedid = $_POST["id"];
}

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// Query MongoDB
$filter = ['_id' => new MongoDB\BSON\ObjectId($selectedid)];
$query = new MongoDB\Driver\Query($filter);
$rows = $manager->executeQuery("$dbName.Screenshots", $query);

foreach ($rows as $row) {
    echo '<img src="data:image/png;base64,' . $row->data . '"/>';
}
?>
