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
                            <div class="card" style="width:130%">
                                <div class="card-body">
                                    <h5 class="card-title">Processes (Intervals)</h5>
                                    <div id="paddingDiv" style="padding: 2%;">
                                        <?php
                                        // MongoDB Connection & Query
                                        $db_user = getenv('DB_ROOT_USERNAME');
                                        $db_password = getenv('DB_ROOT_PASSWORD');
                                        $dbName = getenv('DB_NAME');

                                        $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                                        $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                                        $filter = [];
                                        $options = ['sort' => ['date_time' => -1]];

                                        $query = new MongoDB\Driver\Query($filter, $options);
                                        $rows = $manager->executeQuery("$dbName.Processes", $query);
                                        ?>

                                        <table id="datatable" class="table table-striped" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>UUID</th>
                                                    <th>Trigger Count</th>
                                                    <th>Category</th>
                                                    <th>Data</th>
                                                    <th>Date & Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row) : ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row->uuid) ?></td>
                                                        <td><?= htmlspecialchars($row->trigger_count) ?></td>
                                                        <td><?= htmlspecialchars($row->category) ?></td>
                                                        <td><?= htmlspecialchars($row->data) ?></td>
                                                        <td><?= htmlspecialchars($row->date_time) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
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

<?php
// Close MongoDB connection
$manager = null;
?>
