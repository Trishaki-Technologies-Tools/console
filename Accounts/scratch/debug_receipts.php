<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/settlement_helper.php';

$res = getReceiptCreditsForMode($conn, 'PAYTM-POS');
echo "getReceiptCreditsForMode output:\n";
print_r($res);

$rQuery = "SELECT id, receipt_date, items FROM receipts WHERE items LIKE '%PAYTM-POS%'";
$rRes = $conn->query($rQuery);
while ($r = $rRes->fetch_assoc()) {
    echo "Receipt ID: {$r['id']}, Date: {$r['receipt_date']}, Items: {$r['items']}\n";
}
