<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Updated credentials with hash for "Kishan-trishaki-tech-console#304"
$VALID_USERNAME = 'console@trishaki';
$VALID_PASSWORD_HASH = '$2y$10$qJ2eYduGbqJT0vN0MxqrHufQVTBaWQxpetN0NJm5AJoO8oRXvUDYu';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username and password are required'
            ]);
            exit;
        }
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        // Debug logging
        error_log("=== LOGIN ATTEMPT ===");
        error_log("Received username: '" . $username . "'");
        error_log("Expected username: '" . $VALID_USERNAME . "'");
        error_log("Username match: " . ($username === $VALID_USERNAME ? 'YES' : 'NO'));
        error_log("Password length: " . strlen($password));
        error_log("Hash: " . $VALID_PASSWORD_HASH);
        error_log("Password verify result: " . (password_verify($password, $VALID_PASSWORD_HASH) ? 'SUCCESS' : 'FAILED'));
        
        // Verify credentials
        if ($username === $VALID_USERNAME && password_verify($password, $VALID_PASSWORD_HASH)) {
            // Successful login
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['login_time'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => 'dashboard.html'
            ]);
        } else {
            // Failed login
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username or password'
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>