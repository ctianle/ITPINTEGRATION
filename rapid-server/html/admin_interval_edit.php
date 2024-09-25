<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITP24 Admin Panel (Intervals)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Left column: Navigation Bar -->
            <div class="col-md-2 p-0">
                <?php include 'nav_bar.php'; ?>
            </div>

            <!-- Right column: Interval Edit Form -->
            <div class="col-md-10">
                <div style="padding: 2%;">
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
</body>
</html>
