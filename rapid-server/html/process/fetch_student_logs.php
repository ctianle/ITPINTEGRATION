<?php
$allowed_roles = ['admin', 'invigilator'];
// Secure session cookie settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript from accessing the session cookie
    ini_set('session.cookie_secure', 1);    // Ensure cookies are only sent over HTTPS (enable HTTPS)
    ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF with the SameSite cookie attribute
    session_start(); // Start the session if it's not already active
}

// Implement session timeout: 30 minutes of inactivity will log the user out
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();     // Unset session variables
    session_destroy();   // Destroy the session
    header("Location: ../index.php");  // Redirect to login page
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();  // Update last activity time

// Check if the user is logged in
if (!isset($_SESSION['UserId'])) {
    header("Location: ../index.php");  // Redirect to login page if not authenticated
    exit;
}

// Check if the user has the required role
if (!in_array($_SESSION['UserType'], $allowed_roles)) {
    header("Location: ../overview.php");  // Redirect to overview page if not authorized
    exit;
}
/**
 * Retrieves data from MongoDB and generates an HTML table with DataTables functionality.
 *
 * @return array An associative array containing a boolean indicating if data was found
 *               and the HTML content for the table.
 */
function getLogs()
{
    // MongoDB Connection & Credentials Setup
    $db_user = getenv('DB_ROOT_USERNAME');
    $db_password = getenv('DB_ROOT_PASSWORD');
    $dbName = getenv('DB_NAME');

    // MongoDB connection setup
    $mongoDBConnectionString = "mongodb://$db_user:$db_password@db:27017";
    $manager = new MongoDB\Driver\Manager($mongoDBConnectionString);
    $student_id = $_GET['student_id'];
    $session_id = (int)$_GET['session_id']; 
    // MongoDB Query Setup
    // MongoDB Query Setup
    $filter = [
        'UUID' => ['$regex' => "^$student_id-"],
        'ProctorSessionID' => $session_id,  // No need to wrap integers in quotes if it's a number
        'datatype' => ['$not' => new MongoDB\BSON\Regex('image', 'i')]
    ];
    // Exclude documents where 'type' contains 'image'
    $options = ['sort' => ['date_time' => -1]]; // Sort by date_time descending

    $query = new MongoDB\Driver\Query($filter, $options);
    $rows = $manager->executeQuery("$dbName.Behaviour_logs", $query);

    $hasData = false; // To check if we have any data

    // Generate HTML Table
    $html = '
    <div class="container table-responsive">
        <table id="datatable" class="table table-striped table-bordered logs-table" style="width:100%">
            <thead>
                <tr>
                    <th>UUID</th>
                    <th>Type</th>
                    <th>Data</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($rows as $row) {
        $hasData = true;

        // Convert MongoDB\BSON\UTCDateTime to DateTime
        $timestampUTC = $row->timestamp->toDateTime(); // Convert to PHP DateTime object
        $timestampUTC->setTimezone(new DateTimeZone('Asia/Singapore')); // Adjust timezone

        // Format the DateTime object
        $formattedDate = $timestampUTC->format('Y-m-d H:i:s');

        $html .= '<tr>
            <td>' . htmlspecialchars($row->UUID) . '</td>
            <td>' . htmlspecialchars($row->datatype) . '</td>
            <td>' . htmlspecialchars($row->content) . '</td>
            <td>' . htmlspecialchars($formattedDate) . '</td>
        </tr>';
    }

    $html .= '</tbody>
        </table>
    </div>';

    return ['hasData' => $hasData, 'html' => $html];
}

?>