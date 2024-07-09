<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-16">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>ITP24 Admin Panel (Intervals)</title>
</head>
<body>
<?php
// Define MongoDB connection details
$mongoURI = "mongodb://myuser:mypassword@db:27017";
$database = "rapid";
$collectionName = "defaults";

// Query MongoDB
$manager = new MongoDB\Driver\Manager($mongoURI);
$query = new MongoDB\Driver\Query(['name' => 'intervals']);
$rows = $manager->executeQuery("$database.$collectionName", $query);

// Extract interval values if found
$AWD = $AMD = $PL = $OW = "";
foreach ($rows as $row) {
    $AWD = $row->AWD;
    $AMD = $row->AMD;
    $PL = $row->PL;
    $OW = $row->OW;
}
?>

<?php include 'nav_bar.php'; ?>

<style>
    #paddingDiv {
        padding: 2%;
    }
</style>

<div id="paddingDiv">
    <form action="admin_interval_defaults_process.php" method="POST">
        <div class="mb-3">
            <input type="number" class="form-control" id="AWD" name="AWD" value='<?php echo htmlspecialchars($AWD); ?>'>
            <div id="uuidhelp" class="form-text">Interval Value (Seconds) for AWD (Active Windows Detection)</div>
        </div>
        <div class="mb-3">
            <input type="number" class="form-control" id="AMD" name="AMD" value='<?php echo htmlspecialchars($AMD); ?>'>
            <div id="uuidhelp" class="form-text">Interval Value (Seconds) for AMD (Active Monitor Detection)</div>
        </div>
        <div class="mb-3">
            <input type="number" class="form-control" id="PL" name="PL" value='<?php echo htmlspecialchars($PL); ?>'>
            <div id="uuidhelp" class="form-text">Interval Value (Seconds) for PL (Process List)</div>
        </div>
        <div class="mb-3">
            <input type="number" class="form-control" id="OW" name="OW" value='<?php echo htmlspecialchars($OW); ?>'>
            <div id="uuidhelp" class="form-text">Interval Value (Seconds) for OW (Open Windows)</div>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

</body>
</html>
