<?php
$allowed_roles = ['admin'];
include('auth_check.php');
?>
<html lang="en">

<head>
    <?php include "component/admin_essential.inc.php"; ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel - Screenshots</title>
</head>

<?php
// MongoDB Connection & Credentials Setup
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');
$mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
$manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

// MongoDB Query
$filter = [];
$options = ['sort' => ['timestamp' => -1]];
$query = new MongoDB\Driver\Query($filter, $options);
$rows = $manager->executeQuery("$dbName.Screenshots", $query);
?>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include "component/sidebar.inc.php"; ?>
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
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row) : ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row->UUID) ?></td>
                                                        <td><?= htmlspecialchars($row->datatype) ?></td>
                                                        <td><?= (strlen($row->content) > 10 ? '...' : '') . htmlspecialchars(substr($row->content, -20)) ?></td>
                                                        <td>
                                                            <?php
                                                            if ($row->timestamp instanceof MongoDB\BSON\UTCDateTime) {
                                                                $dateTime = $row->timestamp->toDateTime();
                                                                $dateTime->setTimezone(new DateTimeZone('UTC'));
                                                                echo htmlspecialchars($dateTime->format('d-m-Y H:i:s'));
                                                            } else {
                                                                echo 'Invalid timestamp';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <!-- View Image Button -->
                                                            <button class="btn btn-primary btn-sm" onclick="viewImage('<?= htmlspecialchars($row->content) ?>')">View Image</button>
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

    <!-- Modal for displaying the image -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Screenshot Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" src="" alt="Screenshot" style="width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </div>

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

        // JavaScript function to open the modal and display the base64 image
        function viewImage(base64Data) {
            document.getElementById('modalImage').src = "data:image/jpeg;base64," + base64Data;
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
    </script>
</body>

</html>

<?php
// Close MongoDB connection
$manager = null;
?>
