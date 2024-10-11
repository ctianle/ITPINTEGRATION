<?php

/**
 * Retrieves data from MongoDB and generates an HTML table with DataTables functionality.
 *
 * @return array An associative array containing a boolean indicating if data was found
 *               and the HTML content for the table.
 */
function getProcess() {
    // MongoDB Connection & Credentials Setup
    $db_user = getenv('DB_ROOT_USERNAME');
    $db_password = getenv('DB_ROOT_PASSWORD');
    $dbName = getenv('DB_NAME');

    // MongoDB connection setup
    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

    $student_id = $_GET['student_id'];
    // MongoDB Query Setup
    $filter = [
        'uuid' => ['$regex' => "^$student_id-"],
    ];
    $options = ['sort' => ['date_time' => -1]]; // Sort by date_time descending

    $query = new MongoDB\Driver\Query($filter, $options);
    $rows = $manager->executeQuery("$dbName.Processes", $query);

    $hasData = false; // To check if we have any data

    // Generate HTML Table
    $html = '
    <div class="container table-responsive">
        <table id="datatable" class="table table-striped table-bordered process-table" style="width:100%">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Trigger Count</th>
                    <th>Category</th>
                    <th>Data</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>';
            
    foreach ($rows as $row) {
        $hasData = true;
        $html .= '<tr>
            <td>' . htmlspecialchars($row->uuid) . '</td>
            <td>' . htmlspecialchars($row->trigger_count) . '</td>
            <td>' . htmlspecialchars($row->category) . '</td>
            <td>' . htmlspecialchars($row->data) . '</td>
            <td>' . htmlspecialchars($row->date_time) . '</td>
        </tr>';
    }

    $html .= '</tbody>
        </table>
    </div>';

    return ['hasData' => $hasData, 'html' => $html];
}
?>
