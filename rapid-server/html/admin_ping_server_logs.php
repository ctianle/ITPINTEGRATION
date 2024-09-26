<!DOCTYPE html>
<html lang="en">

<head>
    <title>ITP24 Admin Panel (Directory and Logs)</title>

    <!-- Include Bootstrap and your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Include the custom CSS file -->

    <!-- Optional: Include jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

    <style>
        #paddingDiv {
            padding-top: 2%;
            padding-right: 2%;
            padding-bottom: 2%;
            padding-left: 2%;
        }
    </style>
</head>

<body>

    <!-- Full height container-fluid with flex for sidebar and main content -->
    <div class="container-fluid d-flex p-0" style="height: 100vh;">

        <!-- Sidebar -->
        <?php include 'component/sidebar.inc.php'; ?>

        <!-- Main content with navbar after the sidebar -->
        <div class="main-content flex-grow-1">

            <!-- Include the navbar -->
            <?php include 'nav_bar.php'; ?>

            <main class="container-fluid my-4">
                <div id="paddingDiv">
                    <!-- Padding applies to this area onwards -->

                    <div class="row">
                        <div class="col">
                            <center><h2>Directory</h2></center>
                            <hr>

                            <?php
                            // Clearing Cache
                            clearstatcache();

                            $directory = "Heartbeat/";

                            // Open a directory, and read its contents
                            if (is_dir($directory)) {
                                if ($opendirectory = opendir($directory)) {
                                    while (($file = readdir($opendirectory)) !== false) {
                                        if ($file == "." || $file == "..") {
                                            // Do Nothing 
                                        } else {
                                            echo '<center><a href="Heartbeat/' . $file . '" target="myiframe">' . $file . '</a><center><br>';
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
            </main>
        </div> <!-- End of main content -->
    </div> <!-- End of container-fluid -->

</body>

</html>
