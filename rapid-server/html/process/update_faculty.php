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

use MongoDB\Driver\Manager;
use MongoDB\Driver\BulkWrite;

// Initialise DB Variables.
$db_user = getenv('DB_ROOT_USERNAME');
$db_password = getenv('DB_ROOT_PASSWORD');
$dbName = getenv('DB_NAME');

// MongoDB connection string
$mongoConnectionString = "mongodb://$db_user:$db_password@db:27017";

try {
    $manager = new Manager($mongoConnectionString);

    $postData = file_get_contents('php://input');
    $formData = json_decode($postData, true);

    if (!$formData) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data received']);
        exit;
    }

    // Validate and sanitize userId
    if (!isset($formData['userId']) || !filter_var($formData['userId'], FILTER_VALIDATE_INT) || $formData['userId'] <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user ID. It must be a positive integer.']);
        exit;
    }
    $userId = (int)$formData['userId'];

    // Validate and sanitize userType
    if (!isset($formData['userType']) || empty(trim($formData['userType']))) {
        http_response_code(400);
        echo json_encode(['error' => 'User type is required.']);
        exit;
    }
    $userType = htmlspecialchars(trim($formData['userType']), ENT_QUOTES, 'UTF-8');

    // Validate and sanitize userName
    if (!isset($formData['userName']) || empty(trim($formData['userName'])) || strlen($formData['userName']) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user name. It must be non-empty and no longer than 100 characters.']);
        exit;
    }
    $userName = htmlspecialchars(trim($formData['userName']), ENT_QUOTES, 'UTF-8');

    // Validate and sanitize userEmail
    if (!isset($formData['userEmail']) || !filter_var(trim($formData['userEmail']), FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }
    $userEmail = filter_var(trim($formData['userEmail']), FILTER_SANITIZE_EMAIL);

    $collectionName = 'Users';

    // Hash the plaintext password
    if (isset($formData['passwordHash']) && !empty($formData['passwordHash'])) {
        $hashedPassword = password_hash($formData['passwordHash'], PASSWORD_BCRYPT);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Password is required']);
        exit;
    }

    $bulkWrite = new BulkWrite();

    $filter = ['UserId' => (int)$formData['userId']];
    $updateData = [
        '$set' => [
            'UserType' => $userType,
            'UserName' => $userName,
            'Email' => $userEmail,
            'PasswordHash' => $hashedPassword // Use the hashed password here
        ]
    ];

    $bulkWrite->update($filter, $updateData, ['multi' => false, 'upsert' => false]);

    $manager->executeBulkWrite("$dbName.$collectionName", $bulkWrite);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error updating user: ' . $e->getMessage()]);
    exit;
}
?>