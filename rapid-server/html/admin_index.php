<?php
session_start();
$allowed_roles = ['admin'];
include('auth_check.php');
?>
<html lang="en">

<head>
    <?php
    include "component/admin_essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel</title>
   
</head>
<?php
//=============================================
//     MongoDB Connection & Credentials Setup
//=============================================
// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');
// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);
//=============================================
//   MongoDB Query and Data Display
//=============================================
$filter = [];
$options = ['sort' => ['date_time' => -1]]; // Sort by date_time descending
$query = new MongoDB\Driver\Query($filter, $options);
$rows = $manager->executeQuery("$dbName.Processes", $query);
?>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php
            include "component/sidebar.inc.php";
            ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card" style="width:120%">
                                <div class="card-body">
                                    <h5 class="card-title">Processes</h5>
                                    <div id="paddingDiv">
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
                                    <nav>
                                        <ul class="pagination">
                                            <!-- Add pagination here if necessary -->
                                        </ul>
                                    </nav>
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