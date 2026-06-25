<?php
header('Content-Type: application/json');
require_once 'config.php';

$invoiceNo = $_GET['invoiceNo'] ?? '';

if (!$invoiceNo) {
    echo json_encode(['success' => false, 'error' => 'Invoice number required']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM invoices WHERE invoice_no = ?");
    $stmt->bind_param("s", $invoiceNo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
