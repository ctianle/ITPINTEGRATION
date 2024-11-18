<?php
session_start();
$allowed_roles = ['admin'];
include('auth_check.php');
?>
<html lang="en">

<head>
    <?php
    include "component/admin_essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (UUID List)</title>
</head>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include 'component/sidebar.inc.php'; ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card" style="width:1000px">
                                <div class="card-body">
                                    <h5 class="card-title">UUID Settings</h5>
                                        <div class="card-body">
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
                                                    // Initialise DB Variables.
                                                    $db_user = getenv('DB_ROOT_USERNAME');
                                                    $db_password = getenv('DB_ROOT_PASSWORD');
                                                    $dbName = getenv('DB_NAME');

                                                    // MongoDB connection setup
                                                    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                                                    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                                                    // Query MongoDB for distinct UUIDs from proctoring collection
                                                    $command = new MongoDB\Driver\Command([
                                                        'distinct' => 'proctoring',
                                                        'key' => 'uuid',
                                                    ]);
                                                    $cursor = $manager->executeCommand("$dbName", $command);
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


