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


                    <button type="button" class="btn btn-primary" onclick="javascript:history.back();">
                        <h3>Back to Monitoring Session</h3>
                    </button>


                    <div class="card mt-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 mt-3">
                                <?php
                                require_once 'process/fetch_specific_student.php';
                                ?>
                                <div class="timer-container d-flex align-items-center">
                                    <div class="timer-icon">&#9200;</div> <!-- Timer icon -->
                                    <span id="timer" class="ml-2">00:00</span>
                                </div>
                            </div>
                            <div class="row mt-4 justify-content-center">

                                <!-- Screen (Dynamic Screenshot) -->
                                <div class="col-md-6 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <?php
                                            echo '<div class="activity-icon"><h5 class="card-title mb-2">&#128187; Screenshots</h5></div>'; // Icon for Screen
                                            ?>
                                            <div class="d-flex align-items-center pic-container">
                                                <?php
                                                require_once 'process/fetch_student_screenshot.php';

                                                $ScreenshotData = getScreenshot();
                                                if (!$ScreenshotData) {
                                                    echo '<div class="placeholder">';
                                                    echo '<div class="activity-icon"><h5 class="card-title ml-2">No Data</h5></div>'; // Icon for Screen
                                                    echo '</div>';
                                                }
                                                ?>
                                            </div>
                                            <!-- Somewhere else in your HTML where you want to display the pagination -->
                                            <div class="pagination-container">
                                                <?php
                                                // Echo the pagination wherever you want it to appear
                                                if (!empty($ScreenshotData['screenshotPaginationHTML'])) {
                                                    echo $ScreenshotData['screenshotPaginationHTML'];
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Processes (Dynamic) -->
                                <div class="col-md-6 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <?php
                                            echo '<div class="activity-icon"><h5 class="card-title mb-2">&#128241; Process</h5></div>'; // Icon for Screen
                                            ?>
                                            <div class="d-flex align-items-center table-container">
                                                <?php
                                                require_once 'process/fetch_student_process.php';

                                                // Get process data
                                                $result = getProcess();

                                                // Check if data exists
                                                if (!$result['hasData']) {
                                                    echo '<div class="placeholder">';
                                                    echo '<div class="activity-icon"><h5 class="card-title ml-2">No Data</h5></div>'; // Icon for Screen
                                                    echo '</div>';
                                                } else {
                                                    // Output the HTML content of the table
                                                    echo $result['html'];
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Webcam (Dynamic Snapshot) -->
                                <div class="col-md-6 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <?php
                                            echo '<div class="activity-icon"><h5 class="card-title mb-2">&#128247; Webcam</h5></div>'; // Icon for Screen
                                            ?>
                                            <div class="d-flex align-items-center pic-container">
                                                <?php
                                                 // Get the webcam content and pagination
                                                require_once 'process/fetch_student_webcam.php';
                                                $webcamData = getWebcam();
                                                if (!$webcamData) {
                                                    echo '<div class="placeholder">';
                                                    echo '<div class="activity-icon"><h5 class="card-title ml-2">No Data</h5></div>'; // Icon for Screen
                                                    echo '</div>';
                                                }
                                                ?>
                                            </div>
                                            <!-- Somewhere else in your HTML where you want to display the pagination -->
                                            <div class="pagination-container">
                                                <?php
                                                // Echo the pagination wherever you want it to appear
                                                if (!empty($webcamData['snapshotPaginationHTML'])) {
                                                    echo $webcamData['snapshotPaginationHTML'];
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student Logs -->
                                <div class="col-md-6 mb-4">
                                    <div class="card activity-card">
                                        <div
                                            class="card-body d-flex flex-column justify-content-center align-items-center">
                                            <?php
                                            echo '<div class="activity-icon"><h5 class="card-title mb-2">&#128221; Logs</h5></div>'; // Icon for Screen
                                            ?>
                                            <div class="d-flex align-items-center table-container">
                                                <?php
                                                require_once 'process/fetch_student_logs.php';

                                                // Get process data
                                                $result = getLogs();

                                                // Check if data exists
                                                if (!$result['hasData']) {
                                                    echo '<div class="placeholder">';
                                                    echo '<div class="activity-icon"><h5 class="card-title ml-2">No Data</h5></div>'; // Icon for Screen
                                                    echo '</div>';
                                                } else {
                                                    // Output the HTML content of the table
                                                    echo $result['html'];
                                                }
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