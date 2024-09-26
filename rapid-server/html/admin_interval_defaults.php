<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ITP24 Admin Panel (Intervals)</title>

    <!-- Include Bootstrap and your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Include the custom CSS file -->

    <!-- Optional: Include jQuery and Bootstrap JS if needed -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>
</head>

<body>

    <!-- Full height container-fluid with flex for sidebar and main content -->
    <div class="container-fluid d-flex p-0" style="height: 100vh;">

        <!-- Sidebar -->
        <?php include 'component/sidebar.inc.php'; ?> 

        <!-- Main content including the navbar -->
        <div class="main-content flex-grow-1">
            <?php include 'nav_bar.php'; ?> <!-- Include the navbar -->

            <main class="container-fluid my-4">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="container content">
                            <div class="card shadow-lg p-4">
                                <div class="card-body">
                                    <h5 class="card-title text-center">Default Interval Configurations</h5>

                                    <form action="admin_interval_defaults_process.php" method="POST">
                                        <div class="mb-3">
                                            <label for="AWD" class="form-label">AWD (Active Windows Detection)</label>
                                            <input type="number" class="form-control" id="AWD" name="AWD" value='<?php echo htmlspecialchars($AWD); ?>'>
                                            <small class="form-text text-muted">Interval Value (Seconds)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="AMD" class="form-label">AMD (Active Monitor Detection)</label>
                                            <input type="number" class="form-control" id="AMD" name="AMD" value='<?php echo htmlspecialchars($AMD); ?>'>
                                            <small class="form-text text-muted">Interval Value (Seconds)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="PL" class="form-label">PL (Process List)</label>
                                            <input type="number" class="form-control" id="PL" name="PL" value='<?php echo htmlspecialchars($PL); ?>'>
                                            <small class="form-text text-muted">Interval Value (Seconds)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="OW" class="form-label">OW (Open Windows)</label>
                                            <input type="number" class="form-control" id="OW" name="OW" value='<?php echo htmlspecialchars($OW); ?>'>
                                            <small class="form-text text-muted">Interval Value (Seconds)</small>
                                        </div>

                                        <div class="mb-3">
                                            <label for="KS" class="form-label">KS (Keystrokes)</label>
                                            <input type="number" class="form-control" id="KS" name="KS" value='<?php echo htmlspecialchars($KS); ?>'>
                                            <small class="form-text text-muted">Interval Value (Seconds)</small>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100">Submit</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div> <!-- End of Main Content -->
    </div>

</body>

</html>
