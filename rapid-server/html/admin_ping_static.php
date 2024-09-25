<!DOCTYPE html>
<html lang="en">

<head>
    <title>ITP24 Admin Panel (Heartbeat)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
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
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <!-- Sidebar -->
                <?php include 'nav_bar.php'; ?>
            </div>

            <div class="col-md-10">
                <div id="paddingDiv" style="padding: 2%;">
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

                            // Query MongoDB
                            $query = new MongoDB\Driver\Query([]);
                            $rows = $manager->executeQuery("$dbName.ping", $query);

                            foreach ($rows as $row) {
                                $last_connect_time = strtotime($row->last_connect);
                                $now_time = strtotime('now');
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

                            // Close MongoDB Connection
                            $manager = null;
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
