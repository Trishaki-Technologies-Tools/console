<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to read credentials from password.txt
function getCredentials() {
    $credentialsFile = 'password.txt';
    
    if (!file_exists($credentialsFile)) {
        error_log("Credentials file not found: " . $credentialsFile);
        return false;
    }
    
    $content = file_get_contents($credentialsFile);
    if ($content === false) {
        error_log("Failed to read credentials file");
        return false;
    }
    
    // Normalize line endings and split into lines
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $lines = explode("\n", $content);
    
    $credentials = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Use regex for more flexible parsing
        if (preg_match('/^username\s*-\s*(.+)$/i', $line, $matches)) {
            $credentials['username'] = trim($matches[1]);
        } elseif (preg_match('/^password\s*-\s*(.+)$/i', $line, $matches)) {
            $credentials['password'] = trim($matches[1]);
        }
    }
    
    return $credentials;
}

try {
    // Handle POST request
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
        
        // Get stored credentials
        $credentials = getCredentials();
        
        if (!$credentials || !isset($credentials['username']) || !isset($credentials['password'])) {
            error_log("Failed to load credentials or missing username/password");
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server configuration error'
            ]);
            exit;
        }
        
        // Debug log (remove in production)
        error_log("Attempting login for username: " . $username);
        error_log("Stored username: " . $credentials['username']);
        
        // Verify username and password
        if ($username === $credentials['username'] && password_verify($password, $credentials['password'])) {
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