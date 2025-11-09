<?php
// Start Session
session_start();

// Define constants
define('SITEURL', 'http://localhost/');
define('LOCALHOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'adas');

// Create Database Connection
$conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check Connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>