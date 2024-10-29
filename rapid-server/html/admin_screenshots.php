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
    <title>ITP24 Admin Panel - Screenshots</title>
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
$options = ['sort' => ['timestamp' => -1]]; // Sort by date_time descending
$query = new MongoDB\Driver\Query($filter, $options);
$rows = $manager->executeQuery("$dbName.Screenshots", $query);
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
                                    <h5 class="card-title">Screenshots</h5>
                                    <div id="paddingDiv">
                                    <table id="datatable" class="table table-striped" style="width:100%">
                                        <thead>
                                            <tr>
                                                <th>UUID</th>
                                                <th>Data Type</th>
                                                <th>Content</th>
                                                <th>Date & Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rows as $row) : ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row->UUID) ?></td>
                                                    <td><?= htmlspecialchars($row->datatype)?></td>
                                                    <td><?= htmlspecialchars(substr($row->content, 0, 20)) . (strlen($row->content) > 10 ? '...' : '') ?></td>
                                                    <td>
                                                    <?php
                                                    if ($row->timestamp instanceof MongoDB\BSON\UTCDateTime) {
                                                        // Convert to DateTime object
                                                        $dateTime = $row->timestamp->toDateTime();
                                                        $dateTime->setTimezone(new DateTimeZone('UTC')); // Set to UTC
                                                        echo htmlspecialchars($dateTime->format('d-m-Y H:i:s')); // Format the date
                                                    } else {
                                                        echo 'Invalid timestamp'; // Fallback in case of an unexpected type
                                                    }
                                                    ?>
                                                    </td>
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
