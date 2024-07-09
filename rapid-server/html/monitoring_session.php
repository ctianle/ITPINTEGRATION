<?php
session_start();
?>
<html lang="en">
<head>
    <?php include "component/essential.inc.php"; ?>
    <link rel="stylesheet" href="css/monitoring_session.css">
    <title>Monitoring Session</title>
</head>
<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include "component/sidebar.inc.php"; ?>
            <div class="col py-3">
                <div class="container content">
                    <h1 id="session-heading">Monitoring Session: ICT2108_Test_1</h1>
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="timer-container d-flex align-items-center mb-3">
                                    <div class="timer-icon">&#9200;</div> <!-- Timer icon -->
                                    <span id="timer" class="ml-2">00:00</span>
                                </div>
                                <input type="text" id="searchInput" class="form-control mb-3" placeholder="Search students">
                            </div>
                            <div class="row" id="students-container">
                                <!-- Student avatars will be populated by JavaScript -->
                            </div>
                            <nav>
                                <ul id="pagination" class="pagination justify-content-center">
                                    <!-- Pagination links will be populated by JavaScript -->
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script defer src="js/index.js"></script>
    <script defer src="js/monitoring_session.js"></script>
</body>
</html>
