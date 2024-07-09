<?php
require 'vendor/autoload.php'; // Include MongoDB library

$client = new MongoDB\Client("mongodb://localhost:27017");
$database = $client->RAPID;

function uploadScreenshot($file) {
    global $database;

    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return "File is not an image.";
    } else {
        $imgData = base64_encode(file_get_contents($file["tmp_name"]));
        $date_time = new MongoDB\BSON\UTCDateTime(time() * 1000);

        $document = [
            '_id' => new MongoDB\BSON\ObjectId(),
            'uuid' => 'Screenshots',
            'data' => $imgData,
            'date_time' => $date_time
        ];

        $database->Screenshots->insertOne($document);
        return "Screenshot uploaded successfully.";
    }
}

function uploadSnapshot($file) {
    global $database;

    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return "File is not an image.";
    } else {
        $imgData = base64_encode(file_get_contents($file["tmp_name"]));
        $date_time = new MongoDB\BSON\UTCDateTime(time() * 1000);

        $document = [
            '_id' => new MongoDB\BSON\ObjectId(),
            'uuid' => 'Snapshots',
            'data' => $imgData,
            'date_time' => $date_time
        ];

        $database->Snapshots->insertOne($document);
        return "Snapshot uploaded successfully.";
    }
}

function uploadProcessCsv($file) {
    global $database;

    if (($handle = fopen($file["tmp_name"], "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Assuming the first row is the header

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $document = [
                '_id' => new MongoDB\BSON\ObjectId(),
                'SessionId' => 'SomeSessionId', // Replace with actual SessionId if available
                'ProcessName' => $data[0], // Assuming the first column contains ProcessName
                'CapturedAt' => new MongoDB\BSON\UTCDateTime(time() * 1000)
            ];
            $database->StudentProcesses->insertOne($document);
        }

        fclose($handle);
        return "Process CSV uploaded successfully.";
    } else {
        return "Error opening the CSV file.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $messages = [];

    if (isset($_FILES['screenshot'])) {
        $messages[] = uploadScreenshot($_FILES['screenshot']);
    }

    if (isset($_FILES['snapshot'])) {
        $messages[] = uploadSnapshot($_FILES['snapshot']);
    }

    if (isset($_FILES['process_csv'])) {
        $messages[] = uploadProcessCsv($_FILES['process_csv']);
    }

    foreach ($messages as $message) {
        echo $message . "<br>";
    }
}
?>
