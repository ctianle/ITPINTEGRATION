<?php
require 'vendor/autoload.php';

use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;
use MongoDB\BSON\UTCDateTime;

// Initialise DB Variables
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection setup
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new Manager($mongoDBConnectionString);
$collection = "$dbName.vm_detect";

// Empty the collection(To empty the dummy data if there is too much of it)
// $collection->deleteMany([]);

// Insert dummy data(Populate with dummy data upon first time opening this page)
// $collection->insertOne([
//     'uuid' => 123, 
//     'vmdetectdata' => 456, 
//     'datetime' => new \MongoDB\BSON\UTCDateTime()
// ]);

// Fetch all certificate records base on user input
$query = new Query([]);
$cursor = $manager->executeQuery($collection, $query);
$records = $cursor->toArray();
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

<table id="datatable" class="table table-striped" style="width:100%">
    <thead>
        <tr>
            <th>UUID</th>
            <th>VM Detection Data</th>
            <th>Date & Time</th>
        </tr>
    </thead>
    <tbody>
    <!-- =============================================
     Display All Data from `Certificates` Database
    ============================================= -->
    <?php foreach ($records as $record): ?>
        <tr>
            <td><?php echo htmlspecialchars($record['uuid']); ?></td>
            <td><?php echo htmlspecialchars($record['vmdetectdata']); ?></td>
            <td>
            <?php
                // Set the timezone to Singapore
                $singaporeTimezone = new DateTimeZone('Asia/Singapore');

                // Display the DateTime object in the desired format
                echo htmlspecialchars($record['datetime']->toDateTime()->setTimezone($singaporeTimezone)->format('Y-m-d H:i:s')); // Example format: 2024-07-09 12:34:56
            ?>
        </td>
        </tr>
    <?php endforeach; ?>    
    </tbody>
</table>

<script>
    $(document).ready(function() {
    var table = $('#datatable').DataTable( {
        lengthChange: false,
        dom: 'Blfrtip',
        buttons: [ 'copy', 'csv', 'excel', 'pdf', 'print', 'colvis' ],
        "pageLength":1000,
        //fixedHeader: true
        //"lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ]
    } );
 
    table.buttons().container()
        .appendTo( '#datatable_wrapper .col-md-6:eq(0)' );
} );
</script>