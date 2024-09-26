<?php
session_start();
?>
<html lang="en">
    <head>
        <?php
        include "component/essential.inc.php";
        ?>
        <link rel="stylesheet" href="css/students.css">
            <link rel="stylesheet" href="css/sessions.css">
        <title>Students</title>
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
                            <div class="row">
                                <form id="uploadForm" action="process/insert_student.php" method="post" enctype="multipart/form-data">
                                    <!-- Dropdown to select existing session -->
                                    <div class="col-4 mb-3">
                                        <label for="sessionDropdown" class="form-label">Select Existing Session</label>
                                        <select id="sessionDropdown" name="sessionDropdown" class="form-select">
                                        <!-- Options will be populated dynamically -->
                                        </select>
                                    </div>
                                    <!-- Button to upload CSV -->
                                    <div class="col-8">
                                        <button type="button" class="btn btn-info btn-lg create" onclick="document.getElementById('fileInput').click();">Upload Student List (.CSV)</button>
                                        <input type="file" name="file" id="fileInput" accept=".csv" onchange="document.getElementById('uploadForm').submit();" style="display:none;">
                                    </div>
                                </form>
                            </div>
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Student List</h5>
                                        <div class="table-responsive">
                                            <table class="table students">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Student ID</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Email</th>
                                                        <th scope="col">Session ID</th>
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
                        <h5 class="modal-title" id="editModalLabel">Edit Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm">
                            <div class="mb-3">
                                <label for="editStudentId" class="form-label">Student ID</label>
                                <input type="text" class="form-control" id="editStudentId" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="editStudentName" class="form-label">Name</label>
                                <input type="text" class="form-control" id="editStudentName" required>
                            </div>
                            <div class="mb-3">
                                <label for="editStudentEmail" class="form-label">Email</label>
                                <input type="email" class="form-control" id="editStudentEmail" required>
                            </div>
                            <div class="mb-3">
                                <label for="editSessionId" class="form-label">Session ID</label>
                                <input type="text" class="form-control" id="editSessionId" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </body>
    <script defer src="js/index.js"></script>
    <script defer src="js/students.js"></script>
</html>