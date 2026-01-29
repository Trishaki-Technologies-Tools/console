<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Database configuration - REMOTE SERVER
define('DB_HOST', '82.25.121.32');
define('DB_USER', 'u164024082_console');
define('DB_PASS', 'Trishaki@tech-consoledb#304');
define('DB_NAME', 'u164024082_console');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection (for environments where exceptions aren't thrown)
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => "Database connection failed",
        'error' => $e->getMessage()
    ]);
    exit;
}

// Set charset
$conn->set_charset("utf8");
?>