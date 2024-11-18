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
    <title>ITP24 Admin Panel (Heartbeat)</title>
</head>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include 'component/sidebar.inc.php'; ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col">
                            <div class="card-body">
                                <h1 class="card-title" style="margin-left: 65;">Heartbeat Monitoring</h1>
                                    <div class="card-body">
                                    <div id="auto_refresh">
                                        <?php include 'admin_ping_process.php'; ?>
                                    </div>
                                    <script async>
                                        $(function() {
                                            setInterval(function() {
                                               $('#auto_refresh').load('admin_ping_process.php');
                                            }, 4000);
                                        });
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script defer src="js/index.js"></script>
</body>

</html>

