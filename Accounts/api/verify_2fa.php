<?php
session_start();
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = trim($input['code'] ?? '');

        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Code required']);
            exit;
        }

        // Adjust paths to reach root directory
        require_once '../../GoogleAuthenticator.php';
        $g2fa = new GoogleAuthenticator();
        $secretFile = '../accounts_secret.txt'; // Separate secret for Accounts

        $action = $input['action'] ?? 'verify';

        if ($action === 'setup') {
            $secret = trim($input['secret'] ?? '');
            if (empty($secret)) {
                echo json_encode(['success' => false, 'message' => 'Secret required for setup']);
                exit;
            }

            $verifiedSlice = 0;
            if ($g2fa->verifyCode($secret, $code, 2, null, $verifiedSlice)) {
                // Save the secret
                if (file_put_contents($secretFile, $secret)) {
                    $_SESSION['accounts_2fa_verified'] = true;
                    echo json_encode(['success' => true, 'message' => '2FA Setup Successful']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to save secret']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid Code']);
            }
            exit;
        }

        // VERIFY MODE
        if (!file_exists($secretFile)) {
            echo json_encode(['success' => false, 'message' => '2FA not configured', 'setup_required' => true]);
            exit;
        }

        $secret = trim(file_get_contents($secretFile));
        
        $verifiedSlice = 0;
        if ($g2fa->verifyCode($secret, $code, 2, null, $verifiedSlice)) {
            $_SESSION['accounts_2fa_verified'] = true;
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Code']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
