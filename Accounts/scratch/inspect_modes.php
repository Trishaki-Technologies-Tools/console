<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/settlement_helper.php';
ensurePaymentModesTablesExist($conn);

echo "=== PAYMENT MODES ===\n";
$res = $conn->query("SELECT * FROM payment_modes");
while($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "=== EXISTING MERCHANT SETTLEMENTS ===\n";
$res2 = $conn->query("SELECT * FROM merchant_settlements");
while($r2 = $res2->fetch_assoc()) {
    print_r($r2);
}
