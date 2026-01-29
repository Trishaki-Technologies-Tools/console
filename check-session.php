<?php
session_start();
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        // Check if session is not too old (24 hours)
        $sessionTimeout = 24 * 60 * 60; // 24 hours in seconds
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) < $sessionTimeout) {
            echo json_encode([
                'authenticated' => true,
                'username' => $_SESSION['username'] ?? 'Unknown',
                'login_time' => $_SESSION['login_time']
            ]);
        } else {
            // Session expired
            session_destroy();
            echo json_encode([
                'authenticated' => false,
                'message' => 'Session expired'
            ]);
        }
    } else {
        echo json_encode([
            'authenticated' => false,
            'message' => 'Not authenticated'
        ]);
    }
} catch (Exception $e) {
    error_log("Session check error: " . $e->getMessage());
    echo json_encode([
        'authenticated' => false,
        'message' => 'Session check failed'
    ]);
}
?>