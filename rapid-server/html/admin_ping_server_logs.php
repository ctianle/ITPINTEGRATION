<?php
session_start();
?>
<html lang="en">

<head>
    <?php
    include "component/essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (Directory and Logs)</title>
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
                                            <div class="row">
                                                <div class="col">
                                                    <center><h2>Directory</h2></center>
                                                    <hr>

                                                    <?php
                                                    // Clearing Cache
                                                    clearstatcache();

                                                    $directory = "Heartbeat/";

                                                    // Open a directory and read its contents
                                                    if (is_dir($directory)) {
                                                        if ($opendirectory = opendir($directory)) {
                                                            while (($file = readdir($opendirectory)) !== false) {
                                                                if ($file == "." || $file == "..") {
                                                                    // Do Nothing
                                                                } else {
                                                                    echo '<center><a href="Heartbeat/' . $file . '" target="myiframe">' . $file . '</a></center><br>';
                                                                }
                                                            }
                                                            closedir($opendirectory);
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                                <div class="col">
                                                    <center><h2>Logs Display</h2></center>
                                                    <hr>
                                                    <div class="container-fluid">
                                                        <iframe style="width: 100%; height: 500px;" name="myiframe"></iframe>
                                                    </div>
                                                </div>
                                            </div>
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

?>
