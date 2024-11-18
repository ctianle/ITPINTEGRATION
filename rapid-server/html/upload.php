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
    <title>ITP24 Admin Panel (Upload)</title>
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
                                        <div class="card-body">
                                        <style> #paddingDiv{ padding-top: 2%; padding-right: 2%; padding-bottom: 2%; padding-left: 2%; } </style> <div id="paddingDiv"> <!-- Padding applies to this area onwards -->
                                        
                                        <div class="col-md-6 offset-md-3 mt-5">
                                            <br>
                                            <h1>Upload Proctoring Script</h1>
                                            <form enctype="multipart/form-data" action="upload_process.php" method="post">
                                            <div class="form-group">
                                                <div>
                                                    <input class="form-control form-control-lg" id="fileToUpload" name="fileToUpload" type="file" required>
                                                </div>
                                                <hr>
                                                <button type="submit" class="btn btn-primary">Submit</button>
                                            </form>
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


