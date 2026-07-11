<?php
header('Content-Type: application/json');
require_once 'config.php';

$receiptNo = $_GET['receiptNo'] ?? '';

if (!$receiptNo) {
    echo json_encode(['success' => false, 'error' => 'Receipt number required']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM receipts WHERE receipt_no = ?");
    $stmt->bind_param("s", $receiptNo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
