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
    <title>ITP24 Admin Panel (Heartbeat Server)</title>
</head>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include 'component/sidebar.inc.php'; ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    
                                        <div class="card-body">
                                        <style>
                                            #paddingDiv {
                                                padding: 2%;
                                            }
                                        </style>
                                        <div id="paddingDiv">
                                            <p class="h1">Heartbeat Server</p>
                                            <p>Keep this page open in the background when the examination is going on to accurately capture the timestamp of all of our RaspberryPi connections.</p>
                                            <p>Remember to close this page when the examination ends to avoid inflating the log file.</p>
                                            <p>Detailed logs pertaining to our RaspberryPi connections can be viewed <a href="/admin_ping_server_logs.php" target="_blank">here</a>.</p>

                                            <div class="progress">
                                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <br>

                                            <script>
                                                function ProgressBarUp() {
                                                    $(".progress-bar").animate({
                                                        width: "100%",
                                                    }, 10000);
                                                    ProgressBarDown();
                                                }
                                                function ProgressBarDown() {
                                                    $(".progress-bar").animate({
                                                        width: "10%",
                                                    }, 10000);
                                                    ProgressBarUp();
                                                }
                                                ProgressBarUp();
                                            </script>

                                            <!-- Auto Refresh Table -->
                                            <div id="auto_refresh">
                                                <?php include 'admin_ping_server_process.php'; ?>
                                            </div>
                                            <script async>
                                                $(function() {
                                                    setInterval(function(){
                                                        $('#auto_refresh').load('admin_ping_server_process.php');
                                                    }, 10000);
                                                });
                                            </script>
                                        </div>
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


