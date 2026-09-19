<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/settlement_helper.php';

$modesQuery = "SELECT p.id AS merchant_mode_id, p.mode_name, p.settlement_bank_id, p.type 
               FROM payment_modes p 
               WHERE (LOWER(p.type) = 'merchant' OR (p.settlement_bank_id IS NOT NULL AND p.settlement_bank_id > 0))";
$modesRes = $conn->query($modesQuery);
while ($m = $modesRes->fetch_assoc()) {
    echo "Mode: " . json_encode($m) . "\n";
    $mId = intval($m['merchant_mode_id']);
    $mName = trim($m['mode_name']);
    $bId = intval($m['settlement_bank_id']);
    echo "mId=$mId, mName=$mName, bId=$bId\n";
}

reconcileMerchantSettlements($conn);

echo "merchant_settlements rows:\n";
$sRes = $conn->query("SELECT * FROM merchant_settlements");
while ($r = $sRes->fetch_assoc()) {
    print_r($r);
}
