<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Database configuration
define('DB_HOST', '82.25.121.32');
define('DB_USER', 'u164024082_console');
define('DB_PASS', 'Trishaki@tech-consoledb#304');
define('DB_NAME', 'u164024082_console');

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
