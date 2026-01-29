<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'finance_dashboard');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => "Connection failed: " . $conn->connect_error]);
    exit;
}

// Set charset
$conn->set_charset("utf8");
?>
