<?php
// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');


// Connect to MongoDB
$manager = new MongoDB\Driver\Manager("mongodb://$db_user:$db_password@db:27017");

// Check if file was uploaded
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"]) && isset($_POST["uuid"])) {
    $file = $_FILES["image"];
    $uuid = $_POST["uuid"];

    // Check if file is an image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        echo "File is not an image.";
    } else {
        // Encode image to base64
        $imgData = base64_encode(file_get_contents($file["tmp_name"]));

        // Logging Parameters
        date_default_timezone_set('Asia/Singapore');
        $date_time = date('Y-m-d H:i:s');

        // Insert Data into MongoDB
        $bulk = new MongoDB\Driver\BulkWrite;
        $document = [
            '_id' => new MongoDB\BSON\ObjectId(),
            'uuid' => $uuid,
            'data' => $imgData,
            'date_time' => new MongoDB\BSON\UTCDateTime(strtotime($date_time) * 1000)
        ];
        $bulk->insert($document);

        // Insert into MongoDB
        try {
            $result = $manager->executeBulkWrite('rapid.Screenshots', $bulk);
            header("Location: ../student_overview.php");
            exit;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            echo "Error uploading image: " . $e->getMessage();
        }
    }
}
?>


<!-- The process below is for inserting via JSON Data
 <?php
// Connect to MongoDB
$manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@db:27017");

// Decode POST Request (JSON)
$json = file_get_contents('php://input');
$rawdata = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json), true);

$rawdata = array_values($rawdata);
$data = $rawdata[0];
$UUID = base64_decode($rawdata[1]);

// Logging Parameters
date_default_timezone_set('Asia/Singapore');
$date_time = date('Y-m-d H:i:s');
$date = date('Y-m-d');

// Insert Data into MongoDB
$bulk = new MongoDB\Driver\BulkWrite;
$document = [
    '_id' => new MongoDB\BSON\ObjectId(),
    'uuid' => $UUID,
    'data' => $data,
    'date_time' => new MongoDB\BSON\UTCDateTime(strtotime($date_time) * 1000)
];
$bulk->insert($document);

$result = $manager->executeBulkWrite('rapid.Screenshots', $bulk);
if ($result->getInsertedCount() == 1) {
    echo "Screenshot Data inserted successfully.\n";
} else {
    echo "An Error occurred.\n";
    error_log(print_r("Error inserting data into MongoDB", true), 3, $_SERVER['DOCUMENT_ROOT'] . "/system_error.log");
}
?> -->
