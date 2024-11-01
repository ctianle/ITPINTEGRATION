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
$rows = $manager->executeQuery("$dbName.cert_data", $query);
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
                                                <th>Certificate</th>
                                                <th>Created At</th>
                                                <th>Revoked Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rows as $row) : ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row->uuid) ?></td>
                                                    <td><?= htmlspecialchars($row->certificate) ?></td>
                                                    <td><?= htmlspecialchars($row->created_at) ?></td>
                                                    <td><?= htmlspecialchars($row->revoked ? 'Yes' : 'No') ?></td>
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