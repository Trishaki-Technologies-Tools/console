<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'receipt_utils.php';

try {
    ensureReceiptsTableExists($conn);

    $invoiceNo = trim($_GET['invoiceNo'] ?? ($_GET['invoice_no'] ?? ''));
    $invoiceId = !empty($_GET['invoiceId']) ? intval($_GET['invoiceId']) : (!empty($_GET['invoice_id']) ? intval($_GET['invoice_id']) : (!empty($_GET['id']) ? intval($_GET['id']) : null));

    if ($invoiceId || $invoiceNo) {
        $stmt = $conn->prepare("
            SELECT 
                r.id,
                r.receipt_no,
                r.invoice_id,
                r.invoice_no,
                r.type,
                r.items,
                r.original_total_payable,
                r.cumulative_total_paid,
                r.receipt_date,
                r.created_at,
                c.name as billToName,
                c.phone,
                c.email,
                c.gst_number as gstNumber
            FROM receipts r
            JOIN clients c ON r.client_id = c.id
            WHERE (? > 0 AND r.invoice_id = ?) 
               OR (? != '' AND r.invoice_no = ?)
            ORDER BY r.id ASC
        ");
        $invIdParam = $invoiceId ? $invoiceId : 0;
        $invNoParam = $invoiceNo ? $invoiceNo : '';
        $stmt->bind_param("iiss", $invIdParam, $invIdParam, $invNoParam, $invNoParam);
    } else {
        $fy = getFinancialYearDates();
        $stmt = $conn->prepare("
            SELECT 
                r.id,
                r.receipt_no,
                r.invoice_id,
                r.invoice_no,
                r.type,
                r.items,
                r.original_total_payable,
                r.cumulative_total_paid,
                r.receipt_date,
                r.created_at,
                c.name as billToName,
                c.phone,
                c.email,
                c.gst_number as gstNumber
            FROM receipts r
            JOIN clients c ON r.client_id = c.id
            WHERE r.receipt_date >= ? AND r.receipt_date <= ?
            ORDER BY r.receipt_date DESC, r.id DESC
        ");
        $stmt->bind_param("ss", $fy['start_date'], $fy['end_date']);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $receipts = [];
    while ($row = $result->fetch_assoc()) {
        $rItems = json_decode($row['items'] ?? '[]', true);
        $thisRecAmt = 0;
        if (is_array($rItems)) {
            foreach ($rItems as $ritm) {
                $thisRecAmt += floatval($ritm['paidAmt'] ?? $ritm['amount'] ?? $ritm['totalInclTax'] ?? 0);
            }
        }

        $receipts[] = [
            'paidAmount' => $thisRecAmt,
            'receiptAmount' => $thisRecAmt,
            'id' => $row['id'],
            'receiptNo' => $row['receipt_no'],
            'receipt_no' => $row['receipt_no'],
            'invoiceId' => $row['invoice_id'],
            'invoice_id' => $row['invoice_id'],
            'invoiceNo' => $row['invoice_no'],
            'invoice_no' => $row['invoice_no'],
            'token' => encryptToken($row['receipt_no']),
            'type' => $row['type'],
            'items' => $row['items'],
            'originalTotalPayable' => $row['original_total_payable'],
            'original_total_payable' => $row['original_total_payable'],
            'cumulativeTotalPaid' => $row['cumulative_total_paid'],
            'cumulative_total_paid' => $row['cumulative_total_paid'],
            'billToName' => $row['billToName'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'gstNumber' => $row['gstNumber'],
            'address' => '',
            'date' => $row['receipt_date'],
            'receipt_date' => $row['receipt_date'],
            'generatedAt' => $row['created_at']
        ];
    }
    
    echo json_encode($receipts);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
