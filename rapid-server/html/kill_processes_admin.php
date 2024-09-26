<!DOCTYPE html>
<html>
    
<head>
    <title>ITP24 Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
</head>

<body>
    <?php include 'nav_bar.php'; ?>
    
    <div id="paddingDiv" class="container mt-5">
        <h1>Proctoring Process Killer</h1>
        <form action="save_processes.php" method="post">
            <div class="form-group">
                <label for="processes">Enter Process Numbers (comma-separated):</label>
                <textarea class="form-control" id="processes" name="processes" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Processes</button>
        </form>
        <h2>Common Processes</h2>
        <ol>
            <li>notepad</li>
            <li>calculatorapp</li>
            <li>discord</li>
            <li>chrome</li>
            <li>firefox</li>
            <!-- Add more processes as needed -->
        </ol>
    </div>
</body>

</html>
