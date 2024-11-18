<?php 
 
$allowed_roles = ['admin', 'invigilator'];
include('auth_check.php');
?>
<html lang="en">
<head>
    <?php include "component/essential.inc.php"; ?>
    <link rel="stylesheet" href="css/students.css">
    <link rel="stylesheet" href="css/sessions.css">
    <title>Students</title>
</head>
<body>
    <main class="container-fluid">
        <div class="row flex-nowrap">
            <?php include "component/sidebar.inc.php"; ?>
            <div class="col py-3">
                <div class="container content">
                    <div id="tables-container" class="row">
                        <!-- Tables will be populated by JavaScript -->
                    
                    </div>
                    <nav aria-label="Session navigation">
                            <ul class="pagination" id="session-pagination"></ul>
                        </nav>
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