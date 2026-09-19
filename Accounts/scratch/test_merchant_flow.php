<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/settlement_helper.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/receipt_utils.php';

ensurePaymentModesTablesExist($conn);
ensureReceiptsTableExists($conn);

echo "=== VERIFYING INCOMES AND EXPENSES OUTPUT ===\n";

// 1. Ensure test client exists
$cRes = $conn->query("SELECT id FROM clients WHERE phone = '9999988888' LIMIT 1");
if ($cRes && $cRow = $cRes->fetch_assoc()) {
    $clientId = $cRow['id'];
} else {
    $conn->query("INSERT INTO clients (name, phone, client_type) VALUES ('Merchant Test Client', '9999988888', 'Client')");
    $clientId = $conn->insert_id;
}

// 2. Insert test receipt
$conn->query("DELETE FROM receipts WHERE receipt_no = 'RECP-TEST-001'");
$conn->query("DELETE FROM expenses WHERE description = 'Test Merchant Charge 58'");
$conn->query("DELETE FROM merchant_settlements WHERE txn_date = '2026-09-02'");

$itemsJson = json_encode([[
    'description' => 'Web Development Milestone 1',
    'paymentMode' => 'PAYTM-POS',
    'date' => '2026-09-02',
    'amount' => 3000.00,
    'paidAmt' => 3000.00
]]);

$insStmt = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, 'non-gst', ?, 3000.00, 3000.00, '2026-09-02', 'paid')");
$recNo = 'RECP-TEST-001';
$insStmt->bind_param("sis", $recNo, $clientId, $itemsJson);
$insStmt->execute();

$expStmt = $conn->prepare("INSERT INTO expenses (description, amount, date, payment_mode_id) VALUES ('Test Merchant Charge 58', 58.00, '2026-09-02', 40)");
$expStmt->execute();

reconcileMerchantSettlements($conn);

$sRes = $conn->query("SELECT * FROM merchant_settlements WHERE txn_date = '2026-09-02'");
$sRow = $sRes ? $sRes->fetch_assoc() : null;
echo "Settlement Row: " . json_encode($sRow) . "\n";

// Query Incomes manually using same logic as incomes.php
$fy = getFinancialYearDates();
$incomes = [];
$sQuery = "
    SELECT 
        CONCAT('settle_', s.id) AS id,
        CONCAT('Merchant Settlement from ', m.mode_name) AS description,
        s.amount,
        s.settlement_date AS date,
        COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) AS created_at,
        'Not Added' AS attachment,
        'Merchant Settlement' AS category,
        b.mode_name AS payment_mode,
        1 as is_settlement,
        0 as is_receipt
    FROM merchant_settlements s
    JOIN payment_modes m ON s.merchant_mode_id = m.id
    JOIN payment_modes b ON s.bank_mode_id = b.id
    WHERE s.amount > 0
      AND s.settlement_date >= '{$fy['start_date']}' AND s.settlement_date <= '{$fy['end_date']}'
";
$sRes = $conn->query($sQuery);
while($s = $sRes->fetch_assoc()) {
    $incomes[] = $s;
}

$rQuery = "
    SELECT 
        r.id, r.receipt_no, r.items, r.receipt_date AS date, r.created_at, c.name AS client_name, r.cumulative_total_paid
    FROM receipts r
    JOIN clients c ON r.client_id = c.id
    WHERE r.receipt_date >= '{$fy['start_date']}' AND r.receipt_date <= '{$fy['end_date']}'
";
$rRes = $conn->query($rQuery);
while($rRow = $rRes->fetch_assoc()) {
    $rItems = json_decode($rRow['items'] ?? '[]', true);
    $thisRecAmt = 0;
    $recPaymentMode = 'Online';
    $firstItemDesc = '';
    if (is_array($rItems)) {
        foreach ($rItems as $ritm) {
            $thisRecAmt += floatval($ritm['paidAmt'] ?? $ritm['amount'] ?? 0);
            $rawMode = trim($ritm['paymentMode'] ?? $ritm['payment_mode'] ?? $ritm['mode'] ?? '');
            if (!empty($rawMode)) {
                $recPaymentMode = $rawMode;
            }
            if (empty($firstItemDesc) && !empty($ritm['description'])) {
                $firstItemDesc = trim($ritm['description']);
            }
        }
    }
    if ($thisRecAmt > 0) {
        $clientName = trim($rRow['client_name'] ?? '');
        $desc = !empty($clientName) ? "{$clientName} : {$firstItemDesc}" : $firstItemDesc;
        $incomes[] = [
            'id' => 'rec_' . $rRow['id'],
            'description' => $desc,
            'amount' => $thisRecAmt,
            'date' => $rRow['date'],
            'payment_mode' => $recPaymentMode,
            'category' => 'Sales'
        ];
    }
}

echo "=== INCOMES EXTRACTED ===\n";
foreach ($incomes as $inc) {
    if ($inc['date'] === '2026-09-02' || $inc['date'] === '2026-09-03') {
        echo json_encode($inc) . "\n";
    }
}

// Cleanup
$conn->query("DELETE FROM receipts WHERE receipt_no = 'RECP-TEST-001'");
$conn->query("DELETE FROM expenses WHERE description = 'Test Merchant Charge 58'");
$conn->query("DELETE FROM merchant_settlements WHERE txn_date = '2026-09-02'");
reconcileMerchantSettlements($conn);

echo "=== SUCCESS ===\n";
