<?php
// ============================================================
// logout.php - Logout Handler
// Destroys the session and redirects to login page.
// ============================================================
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Redirect to login page with a message
header("Location: login.php?msg=logged_out");
exit();
?>
