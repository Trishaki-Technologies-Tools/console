<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'receipt_utils.php';
require_once 'settlement_helper.php';

$data = json_decode(file_get_contents('php://input'), true);

$invoiceNo = $_GET['invoiceNo'] ?? ($_GET['invoice_no'] ?? ($data['invoiceNo'] ?? ($data['invoice_no'] ?? '')));
$id = !empty($_GET['id']) ? intval($_GET['id']) : (!empty($data['id']) ? intval($data['id']) : null);
$type = $_GET['type'] ?? ($data['type'] ?? '');

if (!$id && !$invoiceNo) {
    echo json_encode(['success' => false, 'error' => 'Bill / Invoice identifier required']);
    exit;
}

try {
    ensureReceiptsTableExists($conn);

    $invId = null;
    $targetInvNo = null;

    if ($id) {
        $stmt = $conn->prepare("SELECT id, invoice_no FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $invRow = $res->fetch_assoc();
            $invId = $invRow['id'];
            $targetInvNo = $invRow['invoice_no'];
        }
    } elseif ($invoiceNo) {
        if ($type) {
            $stmt = $conn->prepare("SELECT id, invoice_no FROM invoices WHERE invoice_no = ? AND LOWER(type) = LOWER(?) ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("ss", $invoiceNo, $type);
        } else {
            $stmt = $conn->prepare("SELECT id, invoice_no FROM invoices WHERE invoice_no = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("s", $invoiceNo);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $invRow = $res->fetch_assoc();
            $invId = $invRow['id'];
            $targetInvNo = $invRow['invoice_no'];
        }
    }

    if ($invId) {
        // Clear transactions
        $delTStmt = $conn->prepare("DELETE FROM transactions WHERE reference_table = 'invoices' AND reference_id = ?");
        $delTStmt->bind_param("i", $invId);
        $delTStmt->execute();

        // Delete linked receipts for this invoice (by invoice_id or invoice_no)
        $delRStmt = $conn->prepare("DELETE FROM receipts WHERE invoice_id = ? OR (invoice_no = ? AND invoice_no IS NOT NULL AND invoice_no != '')");
        $delRStmt->bind_param("is", $invId, $targetInvNo);
        $delRStmt->execute();

        // Delete invoice row
        $delInvStmt = $conn->prepare("DELETE FROM invoices WHERE id = ?");
        $delInvStmt->bind_param("i", $invId);
        $delInvStmt->execute();

        log_action($conn, 'DELETE', 'invoices', $invId, "Deleted bill/invoice #$invId" . ($targetInvNo ? " ($targetInvNo)" : ""));
        
        try {
            reconcileMerchantSettlements($conn);
        } catch (Throwable $tR) {}

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Bill / Invoice not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
