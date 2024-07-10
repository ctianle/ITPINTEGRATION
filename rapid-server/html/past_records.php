<?php
session_start();
?>
<html lang="en">
<head>
    <?php
    include "component/essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>Past Records</title>
</head>
<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php
            include "component/sidebar.inc.php";
            ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="col-12">
                            <h3>Past Records</h3><br>
                            <a href="sessions.php"><button type="button" class="btn btn-info btn-lg create">Sessions</button></a>
                        </div>
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">History</h5>
                                    <div class="d-flex justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Sort By
                                            </button>
                                            <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                                                <li><a class="dropdown-item" href="#" data-sort="session_id">Session ID</a></li>
                                                <li><a class="dropdown-item" href="#" data-sort="name">Name</a></li>
                                                <li><a class="dropdown-item" href="#" data-sort="status">Status</a></li>
                                                <li><a class="dropdown-item" href="#" data-sort="date">Date</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-3">
                                        <table class="table sessions">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Session ID</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table-body">
                                                <!-- Rows will be populated by JavaScript -->
                                            </tbody>
                                        </table>
                                        <nav>
                                            <ul class="pagination" id="pagination">
                                                <!-- Pagination links will be populated by JavaScript -->
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
<script defer src="js/index.js"></script>
<script defer src="js/past_records.js"></script>
</html>
