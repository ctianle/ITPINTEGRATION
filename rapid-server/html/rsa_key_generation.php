<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>ITP24 Admin Panel (RSA Key Generation)</title>

    <!-- Include Bootstrap and your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Custom CSS file -->

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
                    <div class="col-lg-10">
                        <div class="container content">
                            <div class="card shadow-lg p-4">
                                <div class="card-body">
                                    <h5 class="card-title text-center">RSA Key Pair Generation</h5> <!-- Add title -->

                                    <!-- Public and Private Key Display -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="public_key" class="form-label">Public Key</label>
                                                <textarea class="form-control" id="public_key" rows="15"><?php echo file_get_contents("/var/www/keys/public_rsa.key"); ?></textarea>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="private_key" class="form-label">Private Key</label>
                                                <textarea class="form-control" id="private_key" rows="15"><?php echo file_get_contents("/var/www/keys/private_rsa.key"); ?></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Log Section -->
                                    <div class="mb-4">
                                        <h6>Last Log Entry:</h6>
                                        <p class="bg-light p-3" style="border-radius: 5px;"><?php
                                            // Display Last Line of RSA Key Generation Logs
                                            $file = escapeshellarg("/var/logs/myapp/rsa_key_generation.log"); // For Security Purposes
                                            $line = `tail -n 1 $file`; //Last Line
                                            echo htmlspecialchars($line);
                                        ?></p>
                                    </div>

                                    <!-- Generate New Keys Button -->
                                    <div class="d-grid">
                                        <a href="rsa_key_generation_process.php" class="btn btn-warning btn-lg">Generate New Keys</a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div> <!-- End of Main Content -->
    </div> <!-- End of container-fluid -->

</body>

</html>
