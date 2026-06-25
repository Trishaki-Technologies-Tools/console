<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Load 2FA Configuration
require_once '2fa_config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);


$VALID_USERNAME = 'Trishaki@console';
$VALID_PASSWORD_HASH = '$2y$10$U6YeWhH9ausb3j2QraJ0Cu5JnR.2OQpujxR.ED2TSwiH6D8gNvbKu';

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
            
            // Bypass 2FA if disabled
            if (!defined('ENABLE_2FA') || !ENABLE_2FA) {
                $_SESSION['logged_in'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['login_time'] = time();
                $_SESSION['accounts_2fa_verified'] = true; // Auto-verify Accounts module 2FA

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'redirect' => 'dashboard.php'
                ]);
                exit;
            }
            
            require_once 'GoogleAuthenticator.php';
            $g2fa = new GoogleAuthenticator();
            $secretFile = '2fa_secret.txt';

            // Check if 2FA is set up
            if (file_exists($secretFile)) {
                $secret = trim(file_get_contents($secretFile));
                
                // If code is provided, verify it
                if (isset($input['code'])) {
                     $code = trim($input['code']);
                     $verifiedSlice = 0;
                     if ($g2fa->verifyCode($secret, $code, 2, null, $verifiedSlice)) { // 2 = 60s tolerance
                         
                        // REPLAY CHECK
                        $lastUsedFile = '2fa_last_used.txt';
                        $lastUsedSlice = 0;
                        if (file_exists($lastUsedFile)) {
                            $lastUsedSlice = (int)trim(file_get_contents($lastUsedFile));
                        }

                        if ($verifiedSlice <= $lastUsedSlice) {
                             // REPLAY ATTACK or EXPIRED
                             echo json_encode([
                                'success' => false,
                                'message' => 'Code already used or expired'
                            ]);
                            exit;
                        }

                        // SAVE NEW SLICE
                        file_put_contents($lastUsedFile, $verifiedSlice);

                        // SUCCESS
                        $_SESSION['logged_in'] = true;
                        $_SESSION['username'] = $username;
                        $_SESSION['login_time'] = time();
                        
                        echo json_encode([
                            'success' => true,
                                'message' => 'Login successful',
                            'redirect' => 'dashboard.php'
                        ]);
                     } else {
                        // INVALID CODE
                        echo json_encode([
                            'success' => false,
                            'message' => 'Invalid 2FA Code' 
                        ]);
                     }
                } else {
                    // REQUIRE 2FA
                    echo json_encode([
                        'success' => false,
                        'require_2fa' => true,
                        'message' => 'Please enter 2FA Code'
                    ]);
                }

            } else {
                // 2FA NOT SETUP - First time setup logic
                
                // Check if user is trying to verify the setup
                if (isset($input['setup_code']) && isset($input['setup_secret'])) {
                    $code = trim($input['setup_code']);
                    $tempSecret = trim($input['setup_secret']);
                    
                    $verifiedSlice = 0;
                    if ($g2fa->verifyCode($tempSecret, $code, 2, null, $verifiedSlice)) {
                        // SAVE SECRET
                        if (file_put_contents($secretFile, $tempSecret)) {
                            // Initialize last used slice
                            file_put_contents('2fa_last_used.txt', $verifiedSlice);

                            $_SESSION['logged_in'] = true;
                            $_SESSION['username'] = $username;
                            $_SESSION['login_time'] = time();
                            
                            echo json_encode([
                                'success' => true,
                                'message' => '2FA Setup Complete & Login Successful',
                                'redirect' => 'dashboard.php'
                            ]);
                        } else {
                            echo json_encode([
                                'success' => false,
                                'message' => 'Failed to save 2FA secret'
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Invalid Verification Code'
                        ]);
                    }
                } else {
                    // GENERATE NEW SECRET for SETUP
                    $secret = $g2fa->createSecret();
                    $qrCodeUrl = $g2fa->getQRCodeGoogleUrl('TrishakiConsole', $secret, 'Trishaki Technologies');
                    
                    echo json_encode([
                        'success' => false,
                        'setup_2fa' => true,
                        'secret' => $secret,
                        'qr_code' => $qrCodeUrl,
                        'message' => 'Setup Google Authenticator'
                    ]);
                }
            }

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