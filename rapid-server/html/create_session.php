<?php
 
$allowed_roles = ['admin', 'invigilator'];
include('auth_check.php');
?>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Session</title>
    <?php
    include "component/essential.inc.php";
    ?>
    <link rel="stylesheet" href="css/create_session.css">
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
                            <h3>Create Session</h3><br>
                        </div>
                        <div class="col">
                            <div class="card">
                                <div class="card-body">
                                    <form id="sessionForm" action="process/insert_session.php" method="POST">
                                        <label for="session_name">Session Name</label>
                                        <input type="text" id="session_name" name="session_name" required>

                                        <label for="date">Date</label>
                                        <input type="date" id="date" name="date" required>

                                        <label for="start_time">Start Time</label>
                                        <input type="time" id="start_time" name="start_time" required>

                                        <label for="end_time">End Time</label>
                                        <input type="time" id="end_time" name="end_time" required>

                                        <div class="whitelist">
                                            <button type="button" onclick="addWhitelist()">+ Add Whitelist</button>
                                            <button type="button" onclick="addBlacklist()">+ Add Blacklist</button>
                                        </div>

                                        <div id="whitelistContainer"></div>
                                        <div id="blacklistContainer"></div>

                                        <div class="buttons">
                                            <button type="submit" class="create">Create</button>
                                            <a href="sessions.php"><button type="button"
                                                    class="cancel">Cancel</button></a>
                                        </div>
                                    </form>
                                    <script>
                                        function addWhitelist() {
                                            const container = document.getElementById('whitelistContainer');
                                            const input = document.createElement('input');
                                            input.type = 'text';
                                            input.name = 'whitelist[]';
                                            input.placeholder = 'Whitelist App';
                                            container.appendChild(input);
                                        }

                                        function addBlacklist() {
                                            const container = document.getElementById('blacklistContainer');
                                            const input = document.createElement('input');
                                            input.type = 'text';
                                            input.name = 'blacklist[]';
                                            input.placeholder = 'Blacklist App';
                                            container.appendChild(input);
                                        }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script defer src="js/index.js"></script>
</body>

</html>