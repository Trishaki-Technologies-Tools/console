<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $result = $conn->query("SELECT * FROM payslips ORDER BY created_at DESC");
    $payslips = [];
    while ($row = $result->fetch_assoc()) {
        $payslips[] = $row;
    }
    echo json_encode(['success' => true, 'payslips' => $payslips]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
