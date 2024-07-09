<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ITP24 Admin Panel (UUID List)</title>
</head>
<body>

<?php include 'nav_bar.php'; ?>

<style>
    #paddingDiv {
        padding-top: 2%;
        padding-right: 2%;
        padding-bottom: 2%;
        padding-left: 2%;
    }
</style>
<div id="paddingDiv">

    <?php
    //=============================================
    //             POST Data
    //=============================================
    $uuid = $_POST['uuid'] ?? '';

    //=============================================
    //             Logging Parameters
    //=============================================
    date_default_timezone_set('Asia/Singapore');
    $date_time = date('d-m-Y H:i:s');
    $date = date('d-m-Y');

    //=============================================
    //     MongoDB Connection & Credentials Setup
    //=============================================
    $mongoDBConnectionString = "mongodb://myuser:mypassword@db:27017";
    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

    //=============================================
    //    Delete Data from MongoDB Collection
    //=============================================
    $filter = ['uuid' => $uuid];
    $options = ['limit' => 0];
    $bulkWrite = new MongoDB\Driver\BulkWrite;
    $bulkWrite->delete($filter, $options);
    
    try {
        $result = $manager->executeBulkWrite('rapid.proctoring', $bulkWrite);
        
        // Delete log file
        $logfilelocation = "/var/logs/myapp/Heartbeat/{$uuid}.log";
        $log_file_deleted = unlink($logfilelocation);

        if ($log_file_deleted) {
            echo '<div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Delete Success!</h4>
                <p>The selected data has been successfully deleted.</p>
                <hr>
                <p class="mb-0">Remember that deleted data cannot be recovered!</p>
                <p class="mb-0">To head back to the admin panel to view all unique UUIDs, please click <a href="/admin_uuidlist.php" class="alert-link">here</a>.</p>
            </div>';
        } else {
            echo '<div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Error!</h4>
                <p>An unexpected error occurred while deleting the log file.</p>
                <hr>
                <p class="mb-0">To head back to the admin panel to view all unique UUIDs, please click <a href="/admin_uuidlist.php" class="alert-link">here</a>.</p>
            </div>';
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo '<div class="alert alert-danger" role="alert">
            <h4 class="alert-heading">Error!</h4>
            <p>An unexpected error occurred.</p>
            <hr>
            <p class="mb-0">Please check the error logs for more information.</p>
            <p class="mb-0">To head back to the admin panel to view all unique UUIDs, please click <a href="/admin_uuidlist.php" class="alert-link">here</a>.</p>
        </div>';
        
        logError("MongoDB Delete Error: " . $e->getMessage());
    }

    // Close MongoDB connection
    $manager = null;

    function logError($error) {
        global $date_time;
        $logFile = '/var/logs/myapp/system_error.log';
        $error_message = "\n" . $date_time . " " . $error;
        error_log(print_r($error_message, true), 3, $logFile);
        echo "An Error occurred.\n";
    }
    ?>

</div>

</body>
</html>
