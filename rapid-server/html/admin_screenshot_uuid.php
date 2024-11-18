<?php
 
$allowed_roles = ['admin'];
include('auth_check.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>RAPID C2 Server (Screenshot)</title>
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
<div id="paddingDiv" style="padding: 2%;">
    <table id="datatable" class="table table-striped" style="width:100%">
        <thead>
            <tr>
                <th>UUID</th>
                <th>View Screenshots</th>
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
        $rows = $manager->executeQuery("$dbName.Screenshots", $query);

        $uniqueUUIDs = [];
        foreach ($rows as $row) {
            if (!in_array($row->uuid, $uniqueUUIDs)) {
                $uniqueUUIDs[] = $row->uuid;
                echo '<tr>';
                echo '<td>' . $row->uuid . '</td>';
                echo '<td><form action="admin_screenshot_view.php" method="POST">';
                echo '<input type="hidden" name="uuid" value="' . $row->uuid . '">';
                echo '<button class="btn btn-success" type="submit"> View Screenshots </button>';
                echo '</form></td>';
                echo '</tr>';
            }
        }
        ?>
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
