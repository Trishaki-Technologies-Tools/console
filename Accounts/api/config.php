<?php
// Set timezone to IST
date_default_timezone_set('Asia/Kolkata');

// Load 2FA Configuration
require_once __DIR__ . '/../../2fa_config.php';

// Database configuration - REMOTE SERVER
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
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
?>