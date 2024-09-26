<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITP24 Admin Panel</title>

    <!-- Bootstrap and Datatables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Custom CSS file -->

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

    <!-- Wrap everything in a container-fluid for full width -->
    <div class="container-fluid d-flex p-0" style="height: 100vh;"> <!-- Flex layout to stretch sidebar -->
        
        <!-- Sidebar -->
        <?php include 'component/sidebar.inc.php'; ?> 

        <!-- Main content, including the navbar -->
        <div class="main-content flex-grow-1">
            <?php include 'nav_bar.php'; ?> <!-- Navbar stays untouched -->
            
            <main class="container my-4">
                <div class="row">
                    <div class="col">
                        <div class="container content">
                            <div class="card shadow-lg"> <!-- Added shadow for a sleek look -->
                                <div class="card-body">
                                    <h5 class="card-title text-center">Processes Overview</h5>
                                    <div id="paddingDiv" class="table-responsive">
                                        <table id="datatable" class="table table-striped table-hover">
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
                                            <ul class="pagination justify-content-center">
                                                <!-- Pagination can be handled by DataTables or manually if needed -->
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div> <!-- End of Main Content -->
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

<?php
// Close MongoDB connection
$manager = null;
?>
