<?php
// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<link rel="stylesheet" href="css/main.css">

<div class="col-auto col-md-3 col-xl-2 px-sm-2 px-0 bg-dark">
    <div class="d-flex flex-column align-items-center align-items-sm-start px-3 pt-2 text-white min-vh-100" style="height:100%;">
        <a href="/" class="d-flex align-items-center pt-2 mb-md-0 me-md-auto text-white text-decoration-none">
            <span class="fs-5 d-sm-inline appname">RAPID</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start" id="menu">
            <li class="nav-item">
                <a href="overview.php" class="nav-link align-middle px-0">
                    <i class="fs-4 bi-speedometer2"></i> <span class="ms-1 d-none d-sm-inline">Overview</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="sessions.php" class="nav-link align-middle px-0">
                    <i class="fs-4 bi-calendar"></i> <span class="ms-1 d-none d-sm-inline">Sessions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="students.php" class="nav-link align-middle px-0">
                    <i class="fs-4 bi-people"></i> <span class="ms-1 d-none d-sm-inline">Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="past_records.php" class="nav-link align-middle px-0">
                    <i class="fs-4 bi-journal-bookmark"></i> <span class="ms-1 d-none d-sm-inline">Past Records</span>
                </a>
            </li>
            
            <!-- Only show the "Features" section if the user is an admin -->
            <?php if (isset($_SESSION['UserType']) && $_SESSION['UserType'] === 'admin') : ?>
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
            <?php endif; ?>
        </ul>
        <hr>
        <div class="dropdown pb-4">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="https://github.com/mdo.png" alt="hugenerd" width="30" height="30" class="rounded-circle">
                <span class="d-none d-sm-inline mx-1"><?php echo isset($_SESSION['UserName']) ? $_SESSION['UserName'] : 'Instructor 1'; ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="#">Settings</a></li>
                <li><a class="dropdown-item" href="#">Profile</a></li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li><a class="dropdown-item" href="process/signout.php">Sign out</a></li>
            </ul>
        </div>
    </div>
</div>
