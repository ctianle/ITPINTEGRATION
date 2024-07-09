<?php
require 'vendor/autoload.php';

use MongoDB\Client as MongoDBClient;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// Fetch all certificate records
$query = new MongoDB\Driver\Query([]);
$cursor = $manager->executeQuery("$dbName.cert_data", $query);

$records = [];
foreach ($cursor as $document) {
    $records[] = (array) $document; // Convert BSON document to PHP array
}
?>

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

<style> #paddingDiv{ padding-top: 2%; padding-right: 2%; padding-bottom: 2%; padding-left: 2%; } </style> <div id="paddingDiv"> <!-- Padding applies to this area onwards -->

<div id="paddingDiv">
    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>UUID</th>
                <th>Certificate Value</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['uuid']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($record['certificate'])); ?></td>
                    <td><?php echo htmlspecialchars($record['created_at']); ?></td>
                    <td>
                        <?php if (isset($record['uuid']) && isset($record['certificate']) && isset($record['created_at'])): ?>
                            <form action="cert_action.php" method="POST" style="display:inline;">
                                <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($record['uuid']); ?>">
                                <input type="hidden" name="certificate" value="<?php echo htmlspecialchars($record['certificate']); ?>">
                                <input type="hidden" name="action" value="revoke">
                                <button type="submit" class="btn <?php echo isset($record['revoked']) && $record['revoked'] ? 'btn-danger' : 'btn-primary'; ?>">
                                    <?php echo isset($record['revoked']) && $record['revoked'] ? 'Unrevoke' : 'Revoke'; ?>
                                </button>
                            </form>
                            <form action="cert_action.php" method="POST" style="display:inline;">
                                <input type="hidden" name="uuid" value="<?php echo htmlspecialchars($record['uuid']); ?>">
                                <input type="hidden" name="certificate" value="<?php echo htmlspecialchars($record['certificate']); ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
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
        pageLength: 1000
    });

    table.buttons().container().appendTo('#datatable_wrapper .col-md-6:eq(0)');
});
</script>

</body>
</html>
