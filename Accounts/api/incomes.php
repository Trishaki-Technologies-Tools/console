<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';

try {
    reconcileMerchantSettlements($conn);
} catch (Throwable $e) {}

$fy = getFinancialYearDates();

$query = "SELECT i.id, i.description, i.amount, i.date, i.created_at, i.attachment, 
                 COALESCE(c.category_name, 'Other') as category, 
                 COALESCE(p.mode_name, 'Cash') as payment_mode,
                 0 as is_settlement
          FROM incomes i
          LEFT JOIN incomes_categories c ON i.category_id = c.id
          LEFT JOIN payment_modes p ON i.payment_mode_id = p.id
          WHERE i.date >= '{$fy['start_date']}' AND i.date <= '{$fy['end_date']}'
          ORDER BY i.date DESC";
$result = $conn->query($query);

$incomes = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $incomes[] = $row;
    }
}

// Append T+1 settlement credit entries to Bank account
$sQuery = "
    SELECT 
        CONCAT('settle_', s.id) AS id,
        CONCAT('Merchant Settlement from ', m.mode_name) AS description,
        s.amount,
        s.settlement_date AS date,
        COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) AS created_at,
        'Not Added' AS attachment,
        'Settlement' AS category,
        b.mode_name AS payment_mode,
        1 as is_settlement,
        0 as is_receipt
    FROM merchant_settlements s
    JOIN payment_modes m ON s.merchant_mode_id = m.id
    JOIN payment_modes b ON s.bank_mode_id = b.id
    WHERE COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) <= NOW()
      AND s.amount > 0
      AND s.settlement_date >= '{$fy['start_date']}' AND s.settlement_date <= '{$fy['end_date']}'
";
$sRes = $conn->query($sQuery);
if ($sRes && $sRes->num_rows > 0) {
    while($sRow = $sRes->fetch_assoc()) {
        $incomes[] = $sRow;
    }
}

// Append payment receipts from receipts table
require_once 'receipt_utils.php';
ensureReceiptsTableExists($conn);
$rQuery = "
    SELECT 
        r.id,
        r.receipt_no,
        r.invoice_id,
        r.invoice_no,
        r.type,
        r.items,
        r.receipt_date AS date,
        r.created_at,
        c.name AS client_name,
        r.cumulative_total_paid,
        i.items AS invoice_items
    FROM receipts r
    JOIN clients c ON r.client_id = c.id
    LEFT JOIN invoices i ON r.invoice_id = i.id
    WHERE r.receipt_date >= '{$fy['start_date']}' AND r.receipt_date <= '{$fy['end_date']}'
    ORDER BY r.receipt_date DESC, r.id DESC
";
$rRes = $conn->query($rQuery);
if ($rRes && $rRes->num_rows > 0) {
    while($rRow = $rRes->fetch_assoc()) {
        $rItems = json_decode($rRow['items'] ?? '[]', true);
        $thisRecAmt = 0;
        $recPaymentMode = 'Online';
        $firstItemDesc = '';
        if (is_array($rItems)) {
            foreach ($rItems as $ritm) {
                $thisRecAmt += floatval($ritm['paidAmt'] ?? $ritm['amount'] ?? $ritm['totalInclTax'] ?? 0);
                $rawMode = trim($ritm['paymentMode'] ?? $ritm['payment_mode'] ?? $ritm['mode'] ?? '');
                if (!empty($rawMode)) {
                    $recPaymentMode = $rawMode;
                }
                if (empty($firstItemDesc) && !empty($ritm['description'])) {
                    $firstItemDesc = trim($ritm['description']);
                }
            }
        }
        if ($thisRecAmt <= 0) {
            $thisRecAmt = floatval($rRow['cumulative_total_paid']);
        }
        if ($thisRecAmt <= 0) continue;

        // Fallback to invoice item description if receipt has generic or empty description
        if ((empty($firstItemDesc) || in_array($firstItemDesc, ['Payment Received', 'Course Fee Payment', 'Payment for Invoice', 'Invoice Payment'])) && !empty($rRow['invoice_items'])) {
            $invItems = json_decode($rRow['invoice_items'], true);
            if (is_array($invItems) && !empty($invItems[0]['description'])) {
                $firstItemDesc = trim($invItems[0]['description']);
            }
        }
        if (empty($firstItemDesc)) {
            $firstItemDesc = (strtolower($rRow['type']) === 'gst') ? 'GST Services' : 'Course Fee';
        }

        $clientName = trim($rRow['client_name'] ?? '');
        $desc = !empty($clientName) ? "{$clientName} : {$firstItemDesc}" : $firstItemDesc;
        $category = 'Sales';

        $incomes[] = [
            'id' => 'rec_' . $rRow['id'],
            'raw_id' => $rRow['id'],
            'description' => $desc,
            'amount' => $thisRecAmt,
            'date' => $rRow['date'],
            'created_at' => $rRow['created_at'],
            'attachment' => 'api/generate_receipt.php?receiptNo=' . urlencode($rRow['receipt_no']),
            'category' => $category,
            'payment_mode' => $recPaymentMode,
            'is_settlement' => 0,
            'is_receipt' => 1,
            'receipt_no' => $rRow['receipt_no']
        ];
    }
}

// Sort all incomes by date DESC
usort($incomes, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

echo json_encode($incomes);
if (isset($conn)) $conn->close();
?>
