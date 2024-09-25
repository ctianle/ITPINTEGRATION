<link rel="stylesheet" href="css/main.css">
<!-- Bootstrap 5 and necessary dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<!-- Sidebar Layout -->
<div class="col-auto col-md-2 col-xl-12 px-sm-2 px-0 bg-dark">
    <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-3 text-white min-vh-100" style="height: 100%;">
        <a href="/" class="d-flex align-items-center pb-0 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-5 d-sm-inline">Admin Panel</span>
        </a>
        <hr>
        <!-- Navigation Menu -->
        <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start" id="menu">
            <li class="nav-item">
                <a href="admin_index.php" class="nav-link align-middle px-0">
                    <i class="fs-4 bi-house-door"></i> <span class="ms-1 d-none d-sm-inline">Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link dropdown-toggle align-middle px-0" id="navbarScrollingDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fs-4 bi-grid"></i> <span class="ms-1 d-none d-sm-inline">Features</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark">
                    <li><a class="dropdown-item" href="admin_interval_defaults.php">Default Interval Configurations</a></li>
                    <li><a class="dropdown-item" href="admin_interval.php">Interval Configurations</a></li>
                    <li><a class="dropdown-item" href="upload.php">Upload Proctoring Script</a></li>
                    <li><a class="dropdown-item" href="admin_uuidlist.php">Delete Data for specified UUID</a></li>
                    <li><a class="dropdown-item" href="rsa_key_generation.php">RSA Asymmetric Key Pair Generation</a></li>
                    <li><a class="dropdown-item" href="admin_ping_dynamic.php">Connection Status (Heartbeat - Dynamic)</a></li>
                    <li><a class="dropdown-item" href="admin_ping_static.php">Connection Status (Heartbeat - Static)</a></li>
                    <li><a class="dropdown-item" href="admin_ping_server.php">Heartbeat Server</a></li>
                    <li><a class="dropdown-item" href="admin_ping_server_logs.php">Heartbeat Server Logs</a></li>
                    <li><a class="dropdown-item" href="kill_processes_admin.php">Kill Processes</a></li>
                </ul>
            </li>
            <li class="nav-item">
                <a href="https://github.com/danieltannn/ITP_24" class="nav-link align-middle px-0" target="_blank">
                    <i class="fs-4 bi-github"></i> <span class="ms-1 d-none d-sm-inline">GitHub</span>
                </a>
            </li>
        </ul>
        <hr>
        <!-- User Profile Section -->
        <div class="dropdown pb-4">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="https://github.com/mdo.png" alt="hugenerd" width="30" height="30" class="rounded-circle">
                <span class="d-none d-sm-inline mx-1"> <?php echo isset($_SESSION['UserName']) ? $_SESSION['UserName'] : 'Instructor 1'; ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="process/signout.php">Sign out</a></li>
            </ul>
        </div>
    </div>
</div>
