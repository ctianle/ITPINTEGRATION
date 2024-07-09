<!DOCTYPE html>
<html>

<head>
    <title>ITP24 Admin Panel (UUID List)</title>
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

    <style>
        #paddingDiv {
            padding-top: 2%;
            padding-right: 2%;
            padding-bottom: 2%;
            padding-left: 2%;
        }
    </style>
    <div id="paddingDiv">

        <table id="datatable" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>

                <?php
                // MongoDB Connection & Credentials Setup
                $mongoDBConnectionString = "mongodb://myuser:mypassword@db:27017";
                $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                // Query MongoDB for distinct UUIDs from proctoring collection
                $command = new MongoDB\Driver\Command([
                    'distinct' => 'proctoring',
                    'key' => 'uuid',
                ]);
                $cursor = $manager->executeCommand('rapid', $command);
                $UUIDs = current($cursor->toArray())->values;

                foreach ($UUIDs as $UUID) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($UUID) . '</td>';
                    echo '<td><form action="admin_uuidlist_delete.php" method="POST">';
                    echo '<input type="hidden" name="uuid" value="' . htmlspecialchars($UUID) . '">';
                    echo '<button class="btn btn-danger" type="submit"> Delete </button>';
                    echo '</form></td>';
                    echo '</tr>';
                }

                // Close MongoDB Connection
                $manager = null;
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
