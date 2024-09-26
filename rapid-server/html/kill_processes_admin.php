<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <title>ITP24 Admin Panel (Process Killer)</title>
    
    <!-- Include Bootstrap and custom CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/admin.css"> <!-- Use your custom CSS -->
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
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="container content">
                        <div class="card shadow-lg p-4">
                            <div class="card-body">
                                <h5 class="card-title text-center">Proctoring Process Killer</h5> <!-- Page title -->

                                <!-- Form for killing processes -->
                                <form action="save_processes.php" method="post" class="mb-4">
                                    <div class="form-group mb-3">
                                        <label for="processes" class="form-label">Enter Process Numbers (comma-separated):</label>
                                        <textarea class="form-control" id="processes" name="processes" rows="4" required></textarea>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-danger btn-lg">Save Processes</button>
                                    </div>
                                </form>

                                <!-- Common Processes List -->
                                <h6>Common Processes</h6>
                                <ul class="list-group mb-3">
                                    <li class="list-group-item">notepad</li>
                                    <li class="list-group-item">calculatorapp</li>
                                    <li class="list-group-item">discord</li>
                                    <li class="list-group-item">chrome</li>
                                    <li class="list-group-item">firefox</li>
                                    <!-- Add more processes as needed -->
                                </ul>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

    </div> <!-- End of main content -->
</div> <!-- End of container-fluid -->

</body>
</html>
