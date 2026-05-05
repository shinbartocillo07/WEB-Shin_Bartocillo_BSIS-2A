<?php
// This file connects to the database and has helper functions used by all pages

// Database settings - change these if your database is different
define('DB_HOST', 'localhost');  // The computer where the database is running
define('DB_USER', 'root');       // Username to connect to the database
define('DB_PASS', '');           // Password for the database user
define('DB_NAME', 'school_transport'); // Name of our database

// Connect to the database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check if the connection worked
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the character encoding to UTF-8 (supports many languages)
$conn->set_charset("utf8");

// Start a session so we can remember who is logged in
session_start();

// Helper functions that other pages use

// This function makes text safe for the database
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// This function turns a password into a safe code (hash)
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// This function checks if a password matches the safe code
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// This function sends the browser to another page
function redirect($url) {
    header("Location: $url");
    exit();
}

// This function checks if someone is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// This function gets the user's role (admin, parent, or driver)
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// This function makes sure the user has the right role, or sends them to login
function requireRole($role) {
    if (!isLoggedIn() || getUserRole() !== $role) {
        redirect('login.php');
    }
}
?>