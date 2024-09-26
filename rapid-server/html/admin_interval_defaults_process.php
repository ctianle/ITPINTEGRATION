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
        $AWD = $_POST['AWD'] ?? '';
        $AMD = $_POST['AMD'] ?? '';
        $PL = $_POST['PL'] ?? '';
        $OW = $_POST['OW'] ?? '';

        // Sanitize input data
        $AWD = (int)htmlspecialchars($AWD);
        $AMD = (int)htmlspecialchars($AMD);
        $PL = (int)htmlspecialchars($PL);
        $OW = (int)htmlspecialchars($OW);

        // Update 'defaults' in MongoDB
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update(
            ['name' => 'intervals'],
            ['$set' => [
                'AWD' => $AWD,
                'AMD' => $AMD,
                'PL' => $PL,
                'OW' => $OW
            ]],
            ['multi' => false, 'upsert' => true]  // Change 'upsert' to true
        );

        try {
            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 1000);
            $result = $manager->executeBulkWrite("$dbName.defaults", $bulk, $writeConcern);

            echo '<div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Success!</h4>
                <p>Default values for intervals have been updated.</p>
                <hr>
                <p class="mb-0">To return to the admin panel to view interval defaults, click <a href="/admin_interval_defaults.php" class="alert-link">here</a>.</p>
            </div>';
        } catch (MongoDB\Driver\Exception\Exception $e) {
            echo '<div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Error!</h4>
                <p>An unexpected error occurred.</p>
                <hr>
                <p class="mb-0">Please check the error logs for more information.</p>
                <p class="mb-0">To return to the admin panel to view interval defaults, click <a href="/admin_interval_defaults.php" class="alert-link">here</a>.</p>
            </div>';

            error_log('MongoDB Update Error: ' . $e->getMessage());
        }
        ?>
    </div>
</body>
</html>
