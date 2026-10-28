<?php
// ============================================================
// db.php - Database Connection File
// This file connects PHP to the MySQL database.
// Include this file in every page that needs database access.
// ============================================================

// Database credentials - change these to match your XAMPP setup
define('DB_HOST', 'localhost');       // XAMPP MySQL runs on localhost
define('DB_USER', 'root');            // Default XAMPP MySQL username
define('DB_PASS', '');                // Default XAMPP MySQL password (empty)
define('DB_NAME', 'child_adoption_db');

// Create connection using mysqli (MySQL Improved Extension)
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if connection was successful
if (!$conn) {
    // die() stops execution and shows the error message
    die("Connection Failed: " . mysqli_connect_error());
}

// Set character encoding to UTF-8 for proper text handling
mysqli_set_charset($conn, "utf8");

// ============================================================
// HELPER FUNCTIONS
// ============================================================

/**
 * Sanitize input data to prevent XSS attacks
 * Always use this before displaying user input on screen
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Redirect to a different page
 * Usage: redirect('login.php');
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 * Call this at the top of protected pages
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if logged-in user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if logged-in user is adopter
 */
function isAdopter() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'adopter';
}
?>
