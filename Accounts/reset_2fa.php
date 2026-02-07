<?php
session_start();

// Delete Accounts-specific 2FA file
if (file_exists('accounts_secret.txt')) {
    unlink('accounts_secret.txt');
}

// Clear Accounts verification session
unset($_SESSION['accounts_2fa_verified']);

echo "<h1>Accounts 2FA Reset Successfully</h1>";
echo "<p>The specific secret key for Accounts has been deleted.</p>";
echo "<p><a href='index.php'>Go to Accounts Setup</a></p>";
?>
