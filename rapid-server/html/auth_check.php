<?php
// Secure session cookie settings
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript from accessing the session cookie
ini_set('session.cookie_secure', 1);    // Ensure cookies are only sent over HTTPS (make sure HTTPS is enabled)
ini_set('session.cookie_samesite', 'Strict'); // Prevent CSRF with the SameSite cookie attribute

session_start();

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

?>
