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
    <title>ITP24 Admin Panel (Kill Process)</title>
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
                                    <h5 class="card-title">Proctoring Process Killer</h5>
                                        <div class="card-body">
                                        <form action="save_processes.php" method="post">
                                        <div class="form-group">
                                            <label for="processes">Enter Process Numbers (comma-separated):</label>
                                            <textarea class="form-control" id="processes" name="processes" rows="5" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-3">Save Processes</button>
                                    </form>

                                    <h2 class="mt-4">Common Processes</h2>
                                    <ol>
                                        <li>notepad</li>
                                        <li>calculatorapp</li>
                                        <li>discord</li>
                                        <li>chrome</li>
                                        <li>firefox</li>
                                        <!-- Add more processes as needed -->
                                    </ol>
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
    <script>
    $(document).ready(function() {
        // Initialize any additional scripts if needed
    });
</script>
</body>

</html>


