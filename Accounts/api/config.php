<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Load 2FA Configuration if available
if (file_exists(__DIR__ . '/../../2fa_config.php')) {
    require_once __DIR__ . '/../../2fa_config.php';
}

// Load Financial Year Helper
require_once __DIR__ . '/fy_helper.php';

// Database production configuration
$db_host = 'localhost';
$db_user = 'u345018570_accounts';
$db_pass = 'Trishaki@tech-console#304';
$db_name = 'u345018570_accounts';

// Database testing configuration
// $db_host = 'localhost';
// $db_user = 'root';
// $db_pass = '';
// $db_name = 'u345018570_accounts';

try {
    mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
} catch (mysqli_sql_exception $e) {
    // Fallback to u345018570_accounts if u164024082_accounts is not found on remote
    try {
        $conn = new mysqli($db_host, 'u345018570_accounts', '', 'u345018570_accounts', 3306);
    } catch (mysqli_sql_exception $e2) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Database connection failed",
            'error' => $e2->getMessage(),
            'tip' => "Check if MySQL server is running and database configuration is correct."
        ]);
        exit;
    }
}

// Set charset
$conn->set_charset("utf8");

// Audit Log Helper
function log_action($conn, $action, $table_name, $row_id, $details = '')
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = null;
    $username = $_SESSION['username'] ?? 'System';

    if ($username !== 'System') {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $user_id = $res->fetch_assoc()['id'];
        } else {
            // Insert user if not exists to satisfy foreign key constraint
            $dummy_pass = '$2y$10$U6YeWhH9ausb3j2QraJ0Cu5JnR.2OQpujxR.ED2TSwiH6D8gNvbKu';
            $ins = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $ins->bind_param("ss", $username, $dummy_pass);
            if ($ins->execute()) {
                $user_id = $conn->insert_id;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, row_id, ip_address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issis", $user_id, $action, $table_name, $row_id, $details);
    $stmt->execute();
}

// Encryption helpers for URLs
if (!defined('ENCRYPTION_KEY')) {
    define('ENCRYPTION_KEY', 'TrishakiAccountsSecureKey2026!');
}

if (!function_exists('encryptToken')) {
    function encryptToken($string)
    {
        $cipher = "AES-128-ECB";
        $encrypted = openssl_encrypt($string, $cipher, ENCRYPTION_KEY);
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($encrypted));
    }
}

if (!function_exists('decryptToken')) {
    function decryptToken($token)
    {
        $cipher = "AES-128-ECB";
        $data = str_replace(['-', '_'], ['+', '/'], $token);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        $decoded = base64_decode($data);
        return openssl_decrypt($decoded, $cipher, ENCRYPTION_KEY);
    }
}
?>