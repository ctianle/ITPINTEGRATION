<?php
$allowed_roles = ['admin', 'invigilator'];
include('auth_check.php');
?>
<html lang="en">
    <head>
        <?php
        include "component/essential.inc.php";
        ?>
        <link rel="stylesheet" href="css/overview.css">
        <title>Overview</title>
    </head>
    <body>
        <main class="container-fluid">
            <div class="row flex-nowrap">
                <?php
                include "component/sidebar.inc.php";
                ?>
                <div class="col py-3">
                    <div class="container content">
                        <div class="row" style="width: 100%">
                            <div class="col-md">
                                <div class="col">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Student Vigilance</h5>
                                             <table class="table students">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Student ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Email</th>
                                                        <th scope="col">Session ID</th>
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
                                <div class="col">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Previous Sessions</h5>
                                            <table class="table psessions">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Session ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="previous-sessions">
                                                <!-- Previous sessions will be appended here -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md">
                                <div class="col">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="chart-container" style="width:100%;">
                                                <canvas id="myChart" max-width="280" height="280" ></canvas>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">Upcoming Sessions</h5>
                                            <table class="table usessions">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Session ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Status</th>
                                                        <th scope="col">Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="upcoming-sessions">
                                                    <!-- Upcoming sessions will be appended here -->
                                                </tbody>
                                            </table>
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
    <script src="js/session_overview.js"></script>
    <script src="js/chart.js"></script>
    <!--<script defer src="js/students_overview.js"></script>-->
</html>
