<?php
session_start();
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
                                    <h5 class="card-title">Upcoming Sessions</h5>
                                    <h6>Click on Status to start monitoring</h6>
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
                                                    <th scope="col">Duration</th>
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
                    <form id="editForm">
                        <div class="mb-3">
                            <label for="editSessionName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editSessionName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSessionStatus" class="form-label">Status</label>
                            <select class="form-control" id="editSessionStatus" required>
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="complete">Complete</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSessionDate" class="form-label">Date</label>
                            <input type="date" class="form-control" id="editSessionDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSessionStartTime" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="editSessionStartTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSessionEndTime" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="editSessionEndTime" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSessionDuration" class="form-label">Duration</label>
                            <input type="text" class="form-control" id="editSessionDuration" required>
                        </div>
                        <div class="mb-3">
                            <label for="editBlacklistFile" class="form-label">Blacklist</label>
                            <input type="file" class="form-control" id="editBlacklistFile" accept=".txt">
                        </div>
                        <div class="mb-3">
                            <label for="editWhitelistFile" class="form-label">Whitelist</label>
                            <input type="file" class="form-control" id="editWhitelistFile" accept=".txt">
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
