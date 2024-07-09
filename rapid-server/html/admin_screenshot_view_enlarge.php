<?php
if ($_POST["id"] == NULL) {
    die();
} else {
    $selectedid = $_POST["id"];
}

// Connect to MongoDB
$manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@db:27017");

// Query MongoDB
$filter = ['_id' => new MongoDB\BSON\ObjectId($selectedid)];
$query = new MongoDB\Driver\Query($filter);
$rows = $manager->executeQuery('rapid.Screenshots', $query);

foreach ($rows as $row) {
    echo '<img src="data:image/png;base64,' . $row->data . '"/>';
}
?>
