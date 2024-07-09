<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITP24 Admin Panel (Intervals)</title>
</head>
<body>
    <?php include 'nav_bar.php'; ?>

    <div style="padding: 2%;">
        <?php
        
        // Initialise DB Variables.
        $db_user = getenv('DB_ROOT_USERNAME');
        $db_password = getenv('DB_ROOT_PASSWORD');
        $dbName = getenv('DB_NAME');

        // MongoDB connection setup
        $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
        $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);

        // Retrieve POST data
        $uuid = $_POST['uuid'] ?? '';

        if (empty($uuid)) {
            die("Invalid UUID provided.");
        }

        // Delete operation in MongoDB
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->delete(['uuid' => $uuid]);

        try {
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            $result = $manager->executeBulkWrite("$dbName.intervals", $bulk, $writeConcern);

            echo '<div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Delete Success!</h4>
                <p>The selected data has been successfully deleted.</p>
                <hr>
                <p class="mb-0">Remember that deleted data cannot be recovered!</p>
                <p class="mb-0">To return to the admin panel to edit intervals, click <a href="/admin_interval.php" class="alert-link">here</a>.</p>
            </div>';
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo '<div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Error!</h4>
                <p>An unexpected error occurred.</p>
                <hr>
                <p class="mb-0">Please check the error logs for more information.</p>
                <p class="mb-0">To return to the admin panel to edit intervals, click <a href="/admin_interval.php" class="alert-link">here</a>.</p>
            </div>';

            error_log('MongoDB Delete Error: ' . $e->getMessage());
        }
        ?>
    </div>
</body>
</html>
