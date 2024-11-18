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
    <title>ITP24 Admin Panel (Ping)</title>
</head>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card" style="width:1000px">
                                <div class="card-body">
                                    <h5 class="card-title">Ping Process</h5>
                                    <div class="card-body">
                                        <table id="datatable" class="table table-striped" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>UUID</th>
                                                    <th>Status</th>
                                                    <th>Last Connection</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Initialise DB Variables.
                                                $db_user = getenv('DB_ROOT_USERNAME');
                                                $db_password = getenv('DB_ROOT_PASSWORD');
                                                $dbName = getenv('DB_NAME');

                                                // MongoDB connection setup
                                                $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                                                $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                                                // Query MongoDB for ping collection
                                                $query = new MongoDB\Driver\Query([]);
                                                $rows = $manager->executeQuery("$dbName.ping", $query);

                                                foreach ($rows as $row) {
                                                    $last_connect_time = strtotime($row->last_connect);
                                                    $now_time = time();
                                                    $time_difference = $now_time - $last_connect_time;

                                                    echo '<tr>';
                                                    echo '<td>' . htmlspecialchars($row->uuid) . '</td>';

                                                    if ($time_difference < 10) {
                                                        echo '<td><span class="badge bg-success">Connected</span></td>';
                                                    } elseif ($time_difference >= 10 && $time_difference < 30) {
                                                        echo '<td><span class="badge bg-warning text-dark">Unstable Network</span></td>';
                                                    } elseif ($time_difference >= 30) {
                                                        echo '<td><span class="badge bg-danger">Disconnected</span></td>';
                                                    }

                                                    echo '<td>' . htmlspecialchars($row->last_connect) . '</td>';
                                                    echo '</tr>';
                                                }
                                                ?>
                                            </tbody>
                                        </table>
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
