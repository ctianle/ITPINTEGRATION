<!DOCTYPE html>
<html>

<head>
    <title>ITP24 Admin Panel (Directory and Logs)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
</head>

<body>

<div class="container-fluid">
    <div class="row">
        <!-- Left column: Navigation Bar -->
        <div class="col-md-2 p-0 m-0">
            <?php include 'nav_bar.php'; ?>
        </div>

        <!-- Right column: Directory and Logs -->
        <div class="col-md-9">
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

</body>
</html>
