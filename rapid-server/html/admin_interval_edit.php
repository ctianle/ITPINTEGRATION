<?php
session_start();
?>
<html lang="en">

<head>
    <?php
    include "component/essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>ITP24 Admin Panel (intervals)</title>
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
                                    <h5 class="card-title">Interval Settings</h5>
                                        <div class="card-body">
                                        <?php
                                        // Retrieve POST data
                                        $uuid = $_POST['uuid'] ?? '';
                                        $AWD = $_POST['AWD'] ?? '';
                                        $AMD = $_POST['AMD'] ?? '';
                                        $PL = $_POST['PL'] ?? '';
                                        $OW = $_POST['OW'] ?? '';
                                        $KS = $_POST['KS'] ?? '';
                                        $admin_override = $_POST['admin_override'] ?? 0;

                                        // Display the edit form
                                        ?>
                                        <form action="admin_interval_edit_process.php" method="POST">
                                            <div class="mb-3">
                                                <input type="text" class="form-control" id="uuid" name="uuid" value="<?php echo htmlspecialchars($uuid); ?>" required>
                                                <div class="form-text">UUID</div>
                                            </div>
                                            <div class="mb-3">
                                                <input type="number" class="form-control" id="AWD" name="AWD" value="<?php echo htmlspecialchars($AWD); ?>" required>
                                                <div class="form-text">Interval Value (Seconds) for AWD (Active Windows Detection)</div>
                                            </div>
                                            <div class="mb-3">
                                                <input type="number" class="form-control" id="AMD" name="AMD" value="<?php echo htmlspecialchars($AMD); ?>" required>
                                                <div class="form-text">Interval Value (Seconds) for AMD (Active Monitor Detection)</div>
                                            </div>
                                            <div class="mb-3">
                                                <input type="number" class="form-control" id="PL" name="PL" value="<?php echo htmlspecialchars($PL); ?>" required>
                                                <div class="form-text">Interval Value (Seconds) for PL (Process List)</div>
                                            </div>
                                            <div class="mb-3">
                                                <input type="number" class="form-control" id="OW" name="OW" value="<?php echo htmlspecialchars($OW); ?>" required>
                                                <div class="form-text">Interval Value (Seconds) for OW (Open Windows)</div>
                                            </div>
                                            <div class="mb-3">
                                                <input type="number" class="form-control" id="KS" name="KS" value="<?php echo htmlspecialchars($KS); ?>" required>
                                                <div class="form-text">Interval Value (Seconds) for KS (Keystrokes)</div>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="admin_override" name="admin_override" value="1" <?php echo $admin_override ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="admin_override">Admin Override</label>
                                                <div class="form-text">Checking this means that the proctoring script will not be able to modify the interval value any further.</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </form>
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
