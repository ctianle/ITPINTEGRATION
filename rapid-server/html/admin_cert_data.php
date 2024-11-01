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
                                    <h5 class="card-title">Students' Certificate</h5>
                                    <div id="paddingDiv">
                                        <table id="datatable" class="table table-striped" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>UUID</th>
                                                    <th>Certificate</th>
                                                    <th>Created At</th>
                                                    <th>Revoked Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($row->uuid) ?></td>
                                                        <td><?= htmlspecialchars($row->certificate) ?></td>
                                                        <td><?= htmlspecialchars($row->created_at) ?></td>
                                                        <td><?= htmlspecialchars($row->revoked ? 'Yes' : 'No') ?></td>
                                                        <td>
                                                            <div
                                                                class="action d-flex flex-column flex-md-row align-items-center">
                                                                <button class="btn btn-warning btn-sm me-2"
                                                                    style="min-width: 150px;"
                                                                    onclick="toggleRevoked('<?= htmlspecialchars($row->uuid) ?>')">
                                                                    <?= $row->revoked ? 'Mark Revoked as No' : 'Mark Revoked as Yes' ?>
                                                                </button>
                                                                <button class="btn btn-danger btn-sm"
                                                                    onclick="deleteEntry('<?= htmlspecialchars($row->uuid) ?>')">
                                                                    Delete
                                                                </button>
                                                            </div>

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
        $(document).ready(function () {
            var table = $('#datatable').DataTable({
                lengthChange: false,
                dom: 'Blfrtip',
                buttons: ['copy', 'csv', 'excel', 'pdf', 'print', 'colvis'],
                "pageLength": 1000
            });
            table.buttons().container().appendTo('#datatable_wrapper .col-md-6:eq(0)');
        });

        function toggleRevoked(uuid) {
            // Make an AJAX call to toggle the revoked status
            $.ajax({
                url: 'process/toggle_revoked_status.php', // Your endpoint to handle the request
                type: 'POST',
                data: { uuid: uuid },
                success: function (response) {
                    // Optionally handle the response (e.g., update the table or notify the user)
                    location.reload(); // Reload the page to see the changes
                },
                error: function () {
                    alert('Error toggling revoked status');
                }
            });
        }

        function deleteEntry(uuid) {
            if (confirm('Are you sure you want to delete this entry?')) {
                // Make an AJAX call to delete the entry
                $.ajax({
                    url: 'process/delete_cert_entry.php', // Your endpoint to handle the deletion
                    type: 'POST',
                    data: { uuid: uuid },
                    success: function (response) {
                        // Optionally handle the response (e.g., update the table or notify the user)
                        location.reload(); // Reload the page to see the changes
                    },
                    error: function () {
                        alert('Error deleting entry');
                    }
                });
            }
        }

    </script>
</body>

</html>

<?php
// Close MongoDB connection
$manager = null;
?>