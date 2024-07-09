<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['fileToUpload'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is a CSV
    if($fileType != "csv") {
        echo "Sorry, only CSV files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // If everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            echo "The file ". htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " has been uploaded.";
            // Now process the CSV file and insert data into database
            processCSV($target_file);
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

function processCSV($file) {
    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RAPID";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Open the CSV file
    if (($handle = fopen($file, "r")) !== FALSE) {
        $headers = fgetcsv($handle, 1000, ","); // Get the headers of the file

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Prepare SQL to update or insert data
            $sql = "INSERT INTO students (StudentId, StudentName, Email, SessionId) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    StudentName = VALUES(StudentName), 
                    Email = VALUES(Email), 
                    SessionId = VALUES(SessionId)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issi", $data[0], $data[1], $data[2], $data[3]);
            
            // Execute the statement
            if ($stmt->execute() === FALSE) {
                echo "Error: " . $stmt->error . "<br>";
            }
        }
        fclose($handle);
    }

    $conn->close();
}
?>
