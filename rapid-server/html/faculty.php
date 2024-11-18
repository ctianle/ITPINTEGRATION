<?php 
$allowed_roles = ['admin'];
include('auth_check.php');
 ?>
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
                                    <button type="button" class="btn btn-info btn-lg create" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                                        Add Faculty Member
                                    </button>
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

    <!-- Add Faculty Member Modal -->
    <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-labelledby="addFacultyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addFacultyModalLabel">Add Faculty Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addFacultyForm" action="process/insert_faculty.php" method="post">
                        <div class="mb-3">
                            <label for="addUserId" class="form-label">User ID</label>
                            <input type="text" class="form-control" id="addUserId" name="user_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="addUserType" class="form-label">User Type</label>
                            <select class="form-select" id="addUserType" name="user_type" required>
                                <option value="invigilator">Invigilator</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="addUserName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="addUserName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="addUserEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="addUserEmail" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="addPasswordHash" class="form-label">Password (PlainText)</label>
                            <input type="text" class="form-control" id="addPasswordHash" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Member</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
                            <select class="form-select" id="editUserType" required>
                                <option value="invigilator">Invigilator</option>
                                <option value="admin">Admin</option>
                            </select>
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
                            <label for="editPasswordHash" class="form-label">Password (Enter new Password in PlainText)</label>
                            <input type="text" class="form-control" id="editPassword" required>
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
