<?php
session_start();
?>
<html lang="en">

<head>
    <?php
    include "component/admin_essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (RSA Key Generation)</title>
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
                                    <h5 class="card-title">RSA Key Generation</h5>
                                        <div class="card-body">
                                        <style>
                                            #paddingDiv {
                                                padding-top: 2%;
                                                padding-right: 2%;
                                                padding-bottom: 2%;
                                                padding-left: 2%;
                                            }
                                        </style>
                                        <div id="paddingDiv"> <!-- Padding applies to this area onwards -->

                                            <div class="row">
                                                <div class="col">
                                                    <div class="mb-3">
                                                        <label for="public_key" class="form-label">Public Key:</label>
                                                        <textarea class="form-control" id="public_key" rows="20"><?php echo file_get_contents("/var/www/keys/public_rsa.key"); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="mb-3">
                                                        <label for="private_key" class="form-label">Private Key:</label>
                                                        <textarea class="form-control" id="private_key" rows="20"><?php echo file_get_contents("/var/www/keys/private_rsa.key"); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php
                                            ///////////////////////////////////////////////////
                                            // Display Last Line of RSA Key Generation Logs
                                            ///////////////////////////////////////////////////

                                            // $file = "rsa_key_generation.log";
                                            $file = "/var/logs/myapp/rsa_key_generation.log";
                                            $file = escapeshellarg($file); // For Security Purposes
                                            $line = `tail -n 1 $file`; // Last Line
                                            echo '<div class="alert alert-info">' . htmlspecialchars($line) . '</div>';
                                            ?>

                                            <br>
                                            <br>
                                            <a href="rsa_key_generation_process.php" class="btn btn-warning btn-lg btn-block">Generate New Keys</a>
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


