<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITP24 Admin Panel (Intervals)</title>

    <!-- Bootstrap and Datatables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Custom CSS for styling -->

    <!-- jQuery and Datatables JS -->
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

<?php include 'nav_bar.php'; ?> <!-- Include the navbar -->

<main class="container-fluid my-4">
    <div class="row">
        <div class="col">
            <div class="container content">
                <div class="card shadow-lg"> <!-- Added shadow for a sleek look -->
                    <div class="card-body">
                        <h5 class="card-title text-center">Interval Configurations</h5> <!-- Add title similar to previous pages -->
                        <div id="paddingDiv" class="table-responsive">
                            <table id="datatable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>UUID</th>
                                        <th>AWD</th>
                                        <th>AMD</th>
                                        <th>PL</th>
                                        <th>OW</th>
                                        <th>KS</th>
                                        <th>Admin Override</th>
                                        <th>Edit</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Initialise DB Variables
                                    $db_user = getenv('DB_ROOT_USERNAME');
                                    $db_password = getenv('DB_ROOT_PASSWORD');
                                    $dbName = getenv('DB_NAME');

                                    // MongoDB connection setup
                                    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                                    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                                    // Query MongoDB
                                    $query = new MongoDB\Driver\Query([]);
                                    $rows = $manager->executeQuery("$dbName.intervals", $query);

                                    foreach ($rows as $row) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row->uuid) . '</td>';
                                        echo '<td>' . htmlspecialchars($row->AWD) . '</td>';
                                        echo '<td>' . htmlspecialchars($row->AMD) . '</td>';
                                        echo '<td>' . htmlspecialchars($row->PL) . '</td>';
                                        echo '<td>' . htmlspecialchars($row->OW) . '</td>';
                                        echo '<td>' . htmlspecialchars($row->KS) . '</td>';
                                        echo '<td>' . htmlspecialchars($row->admin_override) . '</td>';
                                        echo '<td><form action="admin_interval_edit.php" method="POST">';
                                        echo '<input type="hidden" name="uuid" value="' . htmlspecialchars($row->uuid) . '">';
                                        echo '<input type="hidden" name="AWD" value="' . htmlspecialchars($row->AWD) . '">';
                                        echo '<input type="hidden" name="AMD" value="' . htmlspecialchars($row->AMD) . '">';
                                        echo '<input type="hidden" name="PL" value="' . htmlspecialchars($row->PL) . '">';
                                        echo '<input type="hidden" name="OW" value="' . htmlspecialchars($row->OW) . '">';
                                        echo '<input type="hidden" name="KS" value="' . htmlspecialchars($row->KS) . '">';
                                        echo '<input type="hidden" name="admin_override" value="' . htmlspecialchars($row->admin_override) . '">';
                                        echo '<button class="btn btn-primary btn-sm" type="submit">Edit</button>';
                                        echo '</form></td>';
                                        echo '<td><form action="admin_interval_delete.php" method="POST">';
                                        echo '<input type="hidden" name="uuid" value="' . htmlspecialchars($row->uuid) . '">';
                                        echo '<button class="btn btn-danger btn-sm" type="submit">Delete</button>';
                                        echo '</form></td>';
                                        echo '</tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <nav>
                                <ul class="pagination justify-content-center">
                                    <!-- Add pagination here if necessary -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

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
