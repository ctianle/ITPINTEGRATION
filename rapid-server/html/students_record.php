<?php
session_start();
?>
<html lang="en">
<head>
    <?php include "component/essential.inc.php"; ?>
    <link rel="stylesheet" href="css/students.css">
    <title>Student Record</title>
</head>
<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include "component/sidebar.inc.php"; ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="header-content">
                        <div class="student-info">
                            <h1 id="student-record-heading">Student Record:</h1>
                            <p id="student-session-id">#001234</p>
                            <p id="student-name">John Doe</p>
                        </div>
                        <div class="student-img"></div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card large-card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table students">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Session ID</th>
                                                    <th scope="col">Quiz Name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody id="records-table-body">
                                                <!-- Table rows will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                        <nav>
                                            <ul class="pagination" id="pagination">
                                                <!-- Pagination links will be populated by JavaScript -->
                                            </ul>
                                        </nav>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <h5>Legend:</h5>
                                            <div>
                                                <span class="status-flagged">&nbsp;&nbsp;&nbsp;&nbsp;</span> Flagged
                                                <span class="status-clear">&nbsp;&nbsp;&nbsp;&nbsp;</span> Clear
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
    <script defer src="js/students_record.js"></script>
</body>
</html>
