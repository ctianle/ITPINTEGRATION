<!DOCTYPE html>
<html>

<head>
    <title>ITP24 Admin Panel</title>

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

<?php include 'nav_bar.php'; ?>

<?php
// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// Handle delete all request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
    $bulk = new MongoDB\Driver\BulkWrite();
    $bulk->delete([]);
    $manager->executeBulkWrite("$dbName.machine_learning_data", $bulk);
    echo "<script>alert('All records have been deleted successfully.');</script>";
}

// Fetch data from MongoDB
$query = new MongoDB\Driver\Query([], ['sort' => ['timestamp' => -1]]);
$cursor = $manager->executeQuery("$dbName.machine_learning_data", $query);
?>

<div id="paddingDiv" style="padding: 2%;">
    <form method="post">
        <button type="submit" name="delete_all" class="btn btn-danger mb-3" onclick="return confirm('Are you sure you want to delete all records?');">Delete All</button>
    </form>
    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>Type</th>
                <th>Content</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cursor as $entry) : ?>
                <?php
                $timestamp = $entry->timestamp instanceof MongoDB\BSON\UTCDateTime
                    ? $entry->timestamp->toDateTime()->setTimezone(new DateTimeZone('Asia/Singapore'))->format('Y-m-d H:i:s')
                    : htmlspecialchars($entry->timestamp);
                ?>
                <tr>
                    <td><?= htmlspecialchars($entry->type) ?></td>
                    <td><?= htmlspecialchars($entry->content) ?></td>
                    <td><?= htmlspecialchars($timestamp) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
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
