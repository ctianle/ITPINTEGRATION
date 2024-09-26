<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>ITP24 Admin Panel (UUID List)</title>

    <!-- Include Bootstrap and your custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Include the custom CSS file -->

    <!-- Optional: Include jQuery and Bootstrap JS if needed -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

    <!-- Custom Styling (if needed) -->
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

        <!-- Main content including the navbar -->
        <div class="main-content flex-grow-1">
            <?php include 'nav_bar.php'; ?> <!-- Include the navbar -->

            <main class="container-fluid my-4">
                <div id="paddingDiv">
                    <div class="col-md-6 offset-md-3 mt-5">
                        <br>
                        <h1 class="text-center">Upload Proctoring Script</h1>
                        <form enctype="multipart/form-data" action="upload_process.php" method="post">
                            <div class="form-group">
                                <div>
                                    <input class="form-control form-control-lg" id="fileToUpload" name="fileToUpload" type="file" required>
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-primary w-100">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div> <!-- End of Main Content -->
    </div>

</body>

</html>
