<?php
session_start();

// Delete global 2FA files
if (file_exists('2fa_secret.txt')) {
    unlink('2fa_secret.txt');
}
if (file_exists('2fa_last_used.txt')) {
    unlink('2fa_last_used.txt');
}

// Clear Accounts verification session
unset($_SESSION['accounts_2fa_verified']);

echo "<h1>2FA Configuration Reset Successfully</h1>";
echo "<p>The secret key has been deleted. You can now set up 2FA again.</p>";
echo "<p><a href='Accounts/'>Go to Accounts Setup</a></p>";
?>
