<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Start transaction
    $conn->begin_transaction();
    
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE receipts");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    $conn->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    if ($conn) $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
