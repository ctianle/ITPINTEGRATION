<?php
$allowed_roles = ['admin', 'invigilator'];
include('auth_check.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<html lang="en">

<head>
    <?php
    include "component/essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/sessions.css">
    <title>Sessions</title>
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
                            <a href="create_session.php">
                                <button type="button" class="btn btn-info btn-lg create">Create Session</button>
                            </a>
                        </div>
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Upcoming/Ongoing Sessions</h5>
                                    <h6>Click on Status to start monitoring</h6>
                                    <div class="d-flex justify-content-end">
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button"
                                                id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                                Sort By
                                            </button>
                                            <ul id="sessionDropdownMenu" class="dropdown-menu" aria-labelledby="sortDropdown">
                                                <li><a class="dropdown-item" href="#" data-sort="session_id">Session
                                                        ID</a></li>
                                                <li><a class="dropdown-item" href="#" data-sort="name">Name</a></li>
                                                <li><a class="dropdown-item" href="#" data-sort="status">Status</a></li>
                                                <li><a class="dropdown-item" href="#" data-sort="date">Date</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table sessions">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Session ID</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Start Time</th>
                                                    <th scope="col">End Time</th>
                                                    <th scope="col">Duration(Mins)</th>
                                                    <th scope="col">Action</th>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <form id="editSessionForm" action="process/update_session.php" method="POST">
                    <div class="mb-3">
                        <label for="editSessionId" class="form-label">Session ID</label>
                     <input type="text" class="form-control" id="editSessionId" name="session_id" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="editSessionName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editSessionName" name="session_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSessionDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="editSessionDate" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSessionStartTime" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="editSessionStartTime" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="editSessionEndTime" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="editSessionEndTime" name="end_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="editBlacklist">Blacklist (comma-separated)</label>
                        <input type="text" class="form-control" id="editBlacklist" name="blacklist" />
                    </div>
                    <div class="mb-3">
                        <label for="editWhitelist">Whitelist (comma-separated)</label>
                        <input type="text" class="form-control" id="editWhitelist" name="whitelist" />
                    </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <script defer src="js/index.js"></script>
    <script defer src="js/sessions.js"></script>
</body>

</html>