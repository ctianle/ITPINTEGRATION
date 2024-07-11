<?php session_start(); ?>
<html lang="en">

<head>
    <?php include "component/essential.inc.php"; ?>
    <link rel="stylesheet" href="css/student_overview.css">
    <title>Student Overview</title>
</head>

<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include "component/sidebar.inc.php"; ?>
            <div class="col py-3">
                <div class="container content">

                    <a href="#" onclick="javascript:history.back();" class="back-link">
                    <button type="button" class="btn btn-primary"><h3>Back to Monitoring Session</h3></button>
                    </a>

                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                <span class="activity-title">John Doe's Activity</span>
                                <div class="timer-container d-flex align-items-center">
                                    <div class="timer-icon">&#9200;</div> <!-- Timer icon -->
                                    <span id="timer" class="ml-2">00:00</span>
                                </div>
                            </div>
                            <div class="row mt-4 justify-content-center">

                                <!-- Screen (Dynamic Screenshot) -->
                                <div class="col-md-8 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <?php
                                            echo '<div class="activity-icon"><h5 class="card-title mb-2">&#128187; Screenshots</h5></div>'; // Icon for Screen
                                            ?>
                                            <div class="d-flex align-items-center pic-container">
                                                <?php
                                                require_once 'process/fetch_student_screenshot.php';

                                                $hasScreenshot = getScreenshot();
                                                if ($hasScreenshot) {
                                                    echo '<div class="placeholder">';
                                                    echo '<div class="activity-icon"><h5 class="card-title ml-2">&#128187; Screenshots</h5></div>'; // Icon for Screen
                                                    echo '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Logs -->
                                <div class="col-md-4 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">&#128221;</div> <!-- Icon for Logs -->
                                                <h5 class="card-title ml-2">Logs</h5>
                                            </div>

                                            <p class="logs-text">
                                                <!-- dynamic logs -->
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Webcam (Dynamic Snapshot) -->
                                <div class="col-md-8 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <?php
                                            echo '<div class="activity-icon"><h5 class="card-title mb-2">&#128247; Webcam</h5></div>'; // Icon for Screen
                                            ?>
                                            <div class="d-flex align-items-center pic-container">
                                                <?php
                                                require_once 'process/fetch_student_webcam.php';

                                                $hasWebcam = getWebcam();
                                                if ($hasWebcam) {
                                                    echo '<div class="placeholder">';
                                                    echo '<div class="activity-icon"><h5 class="card-title ml-2">&#128247; Webcam</h5></div>'; // Icon for Screen
                                                    echo '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Processes (Dynamic) -->
                                <div class="col-md-4 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">&#128241;</div> <!-- Icon for Apps -->
                                                <h5 class="card-title ml-2">Apps</h5>
                                            </div>
                                            <div class="apps-list-container">
                                                <?php
                                                // MongoDB connection
                                                // $manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@db:27017");
                                                // Query MongoDB for the latest processes
                                                //$query = new MongoDB\Driver\Query([], ['sort' => ['CapturedAt' => -1]]);
                                                //$rows = $manager->executeQuery("$dbName.StudentProcesses", $query);
                                                // Display the process list
                                                //echo '<ul class="apps-list">';
                                                //foreach ($rows as $row) {
                                                //    echo '<li>' . $row->ProcessName . '</li>';
                                                //}
                                                //echo '</ul>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script defer src="js/index.js"></script>
    <script defer src="js/session_timer.js"></script>
</body>

</html>