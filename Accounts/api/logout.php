<?php
session_start();
header('Content-Type: application/json');

// Only unset the Accounts module verification
if (isset($_SESSION['accounts_2fa_verified'])) {
    unset($_SESSION['accounts_2fa_verified']);
}

echo json_encode([
    'success' => true, 
    'message' => 'Logged out of Accounts module'
]);
?>
