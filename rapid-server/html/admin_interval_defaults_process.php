<?php
$allowed_roles = ['admin'];
include('auth_check.php');
?>
<html lang="en">

<head>
    <?php
    include "component/admin_essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (intervals)</title>
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
                                    <h5 class="card-title">Interval Settings</h5>
                                        <div class="card-body">
                                        <?php
                                                // Initialise DB Variables.
                                                $db_user = getenv('DB_ROOT_USERNAME');
                                                $db_password = getenv('DB_ROOT_PASSWORD');
                                                $dbName = getenv('DB_NAME');

                                                // MongoDB connection setup
                                                $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                                                $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                                                // Retrieve POST data
                                                $AWD = $_POST['AWD'] ?? '';
                                                $AMD = $_POST['AMD'] ?? '';
                                                $PL = $_POST['PL'] ?? '';
                                                $OW = $_POST['OW'] ?? '';

                                                // Sanitize input data
                                                $AWD = (int)htmlspecialchars($AWD);
                                                $AMD = (int)htmlspecialchars($AMD);
                                                $PL = (int)htmlspecialchars($PL);
                                                $OW = (int)htmlspecialchars($OW);

                                                // Update 'defaults' in MongoDB
                                                $bulk = new MongoDB\Driver\BulkWrite;
                                                $bulk->update(
                                                    ['name' => 'intervals'],
                                                    ['$set' => [
                                                        'AWD' => $AWD,
                                                        'AMD' => $AMD,
                                                        'PL' => $PL,
                                                        'OW' => $OW
                                                    ]],
                                                    ['multi' => false, 'upsert' => true]
                                                );

                                                try {
                                                    $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
                                                    $result = $manager->executeBulkWrite("$dbName.defaults", $bulk, $writeConcern);

                                                    echo '<div class="alert alert-success" role="alert">
                                                        <h4 class="alert-heading">Success!</h4>
                                                        <p>Default values for intervals have been updated.</p>
                                                        <hr>
                                                        <p class="mb-0">To return to the admin panel to view interval defaults, click <a href="/admin_interval_defaults.php" class="alert-link">here</a>.</p>
                                                    </div>';
                                                } catch (MongoDB\Driver\Exception\Exception $e) {
                                                    echo '<div class="alert alert-danger" role="alert">
                                                        <h4 class="alert-heading">Error!</h4>
                                                        <p>An unexpected error occurred.</p>
                                                        <hr>
                                                        <p class="mb-0">Please check the error logs for more information.</p>
                                                        <p class="mb-0">To return to the admin panel to view interval defaults, click <a href="/admin_interval_defaults.php" class="alert-link">here</a>.</p>
                                                    </div>';

                                                    error_log('MongoDB Update Error: ' . $e->getMessage());
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
