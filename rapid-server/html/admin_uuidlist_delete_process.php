<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ITP24 Admin Panel (UUID List)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <style>
        #paddingDiv {
            padding: 2%;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left column: Navigation Bar -->
            <div class="col-md-2 p-0">
                <?php include 'nav_bar.php'; ?>
            </div>

            <!-- Right column: UUID List Processing -->
            <div class="col-md-10">
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

                    //=============================================
                    //     MongoDB Connection & Credentials Setup
                    //=============================================
                    // Initialise DB Variables.
                    $db_user = getenv('DB_ROOT_USERNAME');
                    $db_password = getenv('DB_ROOT_PASSWORD');

                    // MongoDB connection setup
                    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                    //=============================================
                    //    Delete Data from MongoDB Collection
                    //=============================================
                    $filter = ['uuid' => $uuid];
                    $options = ['limit' => 0];
                    $bulkWrite = new MongoDB\Driver\BulkWrite;
                    $bulkWrite->delete($filter, $options);
                    
                    try {
                        $result = $manager->executeBulkWrite("$dbName.proctoring", $bulkWrite);
                        
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
            </div>
        </div>
    </div>
</body>
</html>
