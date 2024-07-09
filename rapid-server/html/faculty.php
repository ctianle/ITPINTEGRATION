<?php session_start(); ?>
<html lang="en">
<head>
    <?php include "component/essential.inc.php"; ?>
    <link rel="stylesheet" href="css/students.css">
    <link rel="stylesheet" href="css/sessions.css">
    <title>Faculty Members</title>
</head>
<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include "component/sidebar.inc.php"; ?>
            <div class="col py-3">
                <div class="container content">
                    <div class="row">
                        <div class="row">
                            <form id="uploadForm" action="process/insert_faculty.php" method="post" enctype="multipart/form-data">
                                <div class="col-8">
                                    <button type="button" class="btn btn-info btn-lg create" onclick="document.getElementById('fileInput').click();">Upload Faculty List (.CSV)</button>
                                    <input type="file" name="file" id="fileInput" accept=".csv" onchange="document.getElementById('uploadForm').submit();" style="display:none;">
                                </div>
                            </form>
                        </div>
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Faculty Members List</h5>
                                    <div class="table-responsive">
                                        <table class="table faculty">
                                            <thead>
                                                <tr>
                                                    <th scope="col">User ID</th>
                                                    <th scope="col">User Type</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Email</th>
                                                    <th scope="col">Password Hash</th>
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
                    <h5 class="modal-title" id="editModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <div class="mb-3">
                            <label for="editUserId" class="form-label">User ID</label>
                            <input type="text" class="form-control" id="editUserId" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="editUserType" class="form-label">User Type</label>
                            <input type="text" class="form-control" id="editUserType" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUserName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editUserName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editUserEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPasswordHash" class="form-label">Password Hash</label>
                            <input type="text" class="form-control" id="editPasswordHash" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript files -->
    <script defer src="js/index.js"></script>
    <script defer src="js/faculty.js"></script>
</body>
</html>
