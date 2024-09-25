<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ITP24 Admin Panel (Intervals)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css">
</head>

<body>

    <?php
    // Initialise DB Variables.
    $db_user = getenv('DB_ROOT_USERNAME');
    $db_password = getenv('DB_ROOT_PASSWORD');
    $database = getenv('DB_NAME');

    // Define MongoDB connection details
    $mongoURI = "mongodb://$db_user:$db_password@db:27017";
    $collectionName = "defaults";

    // Query MongoDB
    $manager = new MongoDB\Driver\Manager($mongoURI);
    $query = new MongoDB\Driver\Query(['name' => 'intervals']);
    $rows = $manager->executeQuery("$database.$collectionName", $query);

    // Extract interval values if found
    $AWD = $AMD = $PL = $OW = $KS = "";
    foreach ($rows as $row) {
        $AWD = $row->AWD;
        $AMD = $row->AMD;
        $PL = $row->PL;
        $OW = $row->OW;
        $KS = $row->KS;
    }
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 p-0">
                <?php include 'nav_bar.php'; ?>
            </div>

            <div class="col-md-10">
                <div class="container my-4">
                    <h2>Interval Settings</h2>
                    <div class="card">
                        <div class="card-body">
                            <form action="admin_interval_defaults_process.php" method="POST">
                                <div class="mb-3">
                                    <label for="AWD" class="form-label">AWD (Active Windows Detection)</label>
                                    <input type="number" class="form-control" id="AWD" name="AWD" value='<?php echo htmlspecialchars($AWD); ?>'>
                                    <div class="form-text">Interval Value (Seconds) for AWD.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="AMD" class="form-label">AMD (Active Monitor Detection)</label>
                                    <input type="number" class="form-control" id="AMD" name="AMD" value='<?php echo htmlspecialchars($AMD); ?>'>
                                    <div class="form-text">Interval Value (Seconds) for AMD.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="PL" class="form-label">PL (Process List)</label>
                                    <input type="number" class="form-control" id="PL" name="PL" value='<?php echo htmlspecialchars($PL); ?>'>
                                    <div class="form-text">Interval Value (Seconds) for PL.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="OW" class="form-label">OW (Open Windows)</label>
                                    <input type="number" class="form-control" id="OW" name="OW" value='<?php echo htmlspecialchars($OW); ?>'>
                                    <div class="form-text">Interval Value (Seconds) for OW.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="KS" class="form-label">KS (Keyboard Strokes)</label>
                                    <input type="number" class="form-control" id="KS" name="KS" value='<?php echo htmlspecialchars($KS); ?>'>
                                    <div class="form-text">Interval Value (Seconds) for KS.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
