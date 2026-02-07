<?php
session_start();
unset($_SESSION['accounts_2fa_verified']);
echo "Accounts 2FA session cleared. <a href='index.php'>Go to Accounts</a>";
?>
