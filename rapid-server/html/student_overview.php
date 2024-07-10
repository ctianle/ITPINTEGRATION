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

                    <a href="#" onclick="javascript:history.back();" class="back-link">&lt; Back to Monitoring
                        Session</a>

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
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">&#128187;</div> <!-- Icon for Screen -->
                                                <h5 class="card-title ml-2">Screen</h5>
                                            </div>
                                            <?php
                                            // Initialise DB Variables.
                                            $db_user = getenv('DB_ROOT_USERNAME');
                                            $db_password = getenv('DB_ROOT_PASSWORD');
                                            $dbName = getenv('DB_NAME');

                                            // MongoDB connection setup
                                            $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
                                            $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

                                            // Query MongoDB for the latest uploaded screen image
                                            $query = new MongoDB\Driver\Query(['uuid' => 'Screenshots'], ['sort' => ['date_time' => -1], 'limit' => 1]);
                                            $rows = $manager->executeQuery("$dbName.Screenshots", $query);
                                            // Display the latest screen image
                                            foreach ($rows as $row) {
                                                echo '<img src="data:image/png;base64,' . $row->data . '" alt="Screen Activity" class="activity-image">';
                                            }
                                            ?>
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
                                            <div class="d-flex align-items-center">
                                                <div class="activity-icon">&#128247;</div> <!-- Icon for Webcam -->
                                                <h5 class="card-title ml-2">Webcam</h5>
                                            </div>
                                            <?php
                                            // Query MongoDB for the latest uploaded webcam image
                                            $query = new MongoDB\Driver\Query(['uuid' => 'Snapshots'], ['sort' => ['date_time' => -1], 'limit' => 1]);
                                            $rows = $manager->executeQuery("$dbName.Snapshots", $query);
                                            // Display the latest webcam image
                                            foreach ($rows as $row) {
                                                echo '<img src="data:image/png;base64,' . $row->data . '" alt="Webcam Activity" class="activity-image">';
                                            }
                                            ?>
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
                                                $manager = new MongoDB\Driver\Manager("mongodb://myuser:mypassword@db:27017");
                                                // Query MongoDB for the latest processes
                                                $query = new MongoDB\Driver\Query([], ['sort' => ['CapturedAt' => -1]]);
                                                $rows = $manager->executeQuery("$dbName.StudentProcesses", $query);
                                                // Display the process list
                                                echo '<ul class="apps-list">';
                                                foreach ($rows as $row) {
                                                    echo '<li>' . $row->ProcessName . '</li>';
                                                }
                                                echo '</ul>';
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
    <script defer src="js/monitoring_student.js"></script>
</body>

</html>