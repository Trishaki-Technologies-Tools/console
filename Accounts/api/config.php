<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Load 2FA Configuration
require_once __DIR__ . '/../../2fa_config.php';

// Database configuration - REMOTE SERVER
define('DB_HOST', '82.25.121.32');
define('DB_USER', 'u164024082_accounts');
define('DB_PASS', 'Trishaki@tech-accounts#304');
define('DB_NAME', 'u164024082_accounts');

// Create connection with explicit timeout and port
try {
    // Set a connection timeout (5 seconds)
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);

    // Set connection timeout in MySQL
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => "Database connection failed",
        'error' => $e->getMessage(),
        'tip' => "Check if your Firewall or Antivirus is blocking port 3306, or if the Remote Server IP is still active."
    ]);
    exit;
}

// Set charset
$conn->set_charset("utf8");

// Encryption helpers for URLs
define('ENCRYPTION_KEY', 'TrishakiAccountsSecureKey2026!');

function encryptToken($string) {
    $cipher = "AES-128-ECB";
    $encrypted = openssl_encrypt($string, $cipher, ENCRYPTION_KEY);
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
}

function decryptToken($token) {
    $cipher = "AES-128-ECB";
    $data = str_replace(['-', '_'], ['+', '/'], $token);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $decoded = base64_decode($data);
    return openssl_decrypt($decoded, $cipher, ENCRYPTION_KEY);
}
?>