<?php
session_start();
?>
<html lang="en">

<head>
    <?php
    include "component/essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (UUID List)</title>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script> 
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script> 
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap5.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script> 
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script> 
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script> 
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
</head>

<body>

    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include 'component/sidebar.inc.php'; ?>

            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">UUID Settings</h5>
                                        <div class="card-body">
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
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script defer src="js/index.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#datatable').DataTable({
                lengthChange: false,
                dom: 'Blfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
                "pageLength": 1000
            });

            table.buttons().container().appendTo('#datatable_wrapper .col-md-6:eq(0)');
        });
    </script>
</body>

</html>

?>
