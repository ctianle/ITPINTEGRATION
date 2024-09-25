<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ITP24 Admin Panel (RSA Key Generation)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
</head>

<body>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <!-- Sidebar -->
                <?php include 'nav_bar.php'; ?>
            </div>

            <div class="col-md-10">
                <style>
                    #paddingDiv {
                        padding-top: 2%;
                        padding-right: 2%;
                        padding-bottom: 2%;
                        padding-left: 2%;
                    }
                </style>
                <div id="paddingDiv"> <!-- Padding applies to this area onwards -->

                    <div class="row">
                        <div class="col">
                            <div class="mb-3">
                                <label for="public_key" class="form-label">Public Key:</label>
                                <textarea class="form-control" id="public_key" rows="20"><?php echo file_get_contents("/var/www/keys/public_rsa.key"); ?></textarea>
                            </div>
                        </div>
                        <div class="col">
                            <div class="mb-3">
                                <label for="private_key" class="form-label">Private Key:</label>
                                <textarea class="form-control" id="private_key" rows="20"><?php echo file_get_contents("/var/www/keys/private_rsa.key"); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <?php
                    ///////////////////////////////////////////////////
                    // Display Last Line of RSA Key Generation Logs
                    ///////////////////////////////////////////////////

                    // $file = "rsa_key_generation.log";
                    $file = "/var/logs/myapp/rsa_key_generation.log";
                    $file = escapeshellarg($file); // For Security Purposes
                    $line = `tail -n 1 $file`; // Last Line
                    echo '<div class="alert alert-info">' . htmlspecialchars($line) . '</div>';
                    ?>

                    <br>
                    <br>
                    <a href="rsa_key_generation_process.php" class="btn btn-warning btn-lg btn-block">Generate New Keys</a>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
