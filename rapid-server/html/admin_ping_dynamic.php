<!DOCTYPE html>
<html lang="en">

<head>
    <title>ITP24 Admin Panel (Heartbeat)</title>

    <!-- Bootstrap and Datatables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    
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

<!-- Full height container-fluid with flex for sidebar and main content -->
<div class="container-fluid d-flex p-0" style="height: 100vh;">

    <!-- Sidebar -->
    <?php include 'component/sidebar.inc.php'; ?>

    <!-- Main content -->
    <div class="main-content flex-grow-1">
        <main class="container-fluid my-4">
            <!-- Auto Refresh Table -->
            <div id="auto_refresh">
                <?php include 'admin_ping_process.php'; ?>
            </div>
            <script async>
                $(function() {
                    setInterval(function(){
                        $('#auto_refresh').load('admin_ping_process.php');
                    },4000);
                });
            </script>
        </main>
    </div> <!-- End of main content -->
    
</div> <!-- End of container-fluid -->

</body>

</html>
