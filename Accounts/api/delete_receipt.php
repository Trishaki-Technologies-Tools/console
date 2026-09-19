<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';

$jsonInput = json_decode(file_get_contents('php://input'), true);

$receiptNo = $_GET['receiptNo'] ?? $_GET['receipt_no'] ?? $_POST['receiptNo'] ?? $_POST['receipt_no'] ?? ($jsonInput['receiptNo'] ?? ($jsonInput['receipt_no'] ?? ''));

if (!$receiptNo) {
    echo json_encode(['success' => false, 'error' => 'Receipt number required']);
    exit;
}

try {
    // 1. Find receipt and linked invoice before deleting
    $getStmt = $conn->prepare("SELECT id, invoice_no FROM receipts WHERE receipt_no = ? ORDER BY id DESC LIMIT 1");
    $getStmt->bind_param("s", $receiptNo);
    $getStmt->execute();
    $res = $getStmt->get_result();
    
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $recId = $row['id'];
        $invoiceNo = $row['invoice_no'];

        // 2. Delete the receipt
        $stmt = $conn->prepare("DELETE FROM receipts WHERE id = ?");
        $stmt->bind_param("i", $recId);
        $stmt->execute();
        log_action($conn, 'DELETE', 'receipts', $recId, "Deleted receipt: $receiptNo");

        // 3. Sync and update invoice cumulative paid & status
        if ($invoiceNo) {
            recalculateAndSyncInvoiceTotals($conn, $invoiceNo);
        }

        try {
            reconcileMerchantSettlements($conn);
        } catch (Throwable $tR) {}

        echo json_encode(['success' => true, 'invoiceNo' => $invoiceNo]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Receipt not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function recalculateAndSyncInvoiceTotals($conn, $invoiceNo) {
    if (!$invoiceNo) return;

    // Sum up all remaining receipts for this invoice
    $stmt = $conn->prepare("SELECT items FROM receipts WHERE invoice_no = ?");
    $stmt->bind_param("s", $invoiceNo);
    $stmt->execute();
    $res = $stmt->get_result();

    $newCumulativePaid = 0;
    while ($r = $res->fetch_assoc()) {
        $items = json_decode($r['items'] ?? '[]', true);
        if (is_array($items)) {
            foreach ($items as $itm) {
                $newCumulativePaid += floatval($itm['paidAmt'] ?? $itm['amount'] ?? 0);
            }
        }
    }

    // Get original invoice total payable
    $invStmt = $conn->prepare("SELECT original_total_payable, amount FROM invoices WHERE invoice_no = ? LIMIT 1");
    $invStmt->bind_param("s", $invoiceNo);
    $invStmt->execute();
    $invRes = $invStmt->get_result();
    if ($invRow = $invRes->fetch_assoc()) {
        $originalTotal = floatval($invRow['original_total_payable'] ?? $invRow['amount'] ?? 0);
        
        $newStatus = 'pending';
        if ($newCumulativePaid >= $originalTotal - 0.01 && $originalTotal > 0) {
            $newStatus = 'paid';
        } elseif ($newCumulativePaid > 0) {
            $newStatus = 'partially_paid';
        }

        // Update invoices table
        $updateStmt = $conn->prepare("UPDATE invoices SET cumulative_total_paid = ?, status = ? WHERE invoice_no = ?");
        $updateStmt->bind_param("dss", $newCumulativePaid, $newStatus, $invoiceNo);
        $updateStmt->execute();
    }
}
?>