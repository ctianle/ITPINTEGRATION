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
            'date_time' => new MongoDB\BSON\UTCDateTime(strtotime($date_time) * 1000),
        ];
        $bulk->insert($document);

        // Insert into MongoDB
        try {
            $result = $manager->executeBulkWrite("$dbName.Snapshots", $bulk);
            header("Location: ../student_overview.php"); // Redirect to overview page
            exit;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            echo "Error uploading image: " . $e->getMessage();
        }
    }
}
?>
