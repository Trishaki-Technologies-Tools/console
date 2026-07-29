<?php
header('Content-Type: application/json');
require_once 'config.php';

$invoiceNo = $_GET['invoiceNo'] ?? '';

if (!$invoiceNo) {
    echo json_encode(['success' => false, 'error' => 'Invoice number required']);
    exit;
}

try {
    // Get the invoice ID first so we can delete its transactions
    $getStmt = $conn->prepare("SELECT id FROM invoices WHERE invoice_no = ?");
    $getStmt->bind_param("s", $invoiceNo);
    $getStmt->execute();
    $res = $getStmt->get_result();
    if ($res->num_rows > 0) {
        $invId = $res->fetch_assoc()['id'];
        // Clear transactions
        $delTStmt = $conn->prepare("DELETE FROM transactions WHERE reference_table = 'invoices' AND reference_id = ?");
        $delTStmt->bind_param("i", $invId);
        $delTStmt->execute();
    }

    $stmt = $conn->prepare("DELETE FROM invoices WHERE invoice_no = ?");
    $stmt->bind_param("s", $invoiceNo);
    
    if ($stmt->execute()) {
        log_action($conn, 'DELETE', 'invoices', null, "Deleted invoice: $invoiceNo");
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
