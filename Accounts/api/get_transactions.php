<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $fy = getFinancialYearDates();
    $stmt = $conn->prepare("SELECT id, type, amount, date, reference_id, reference_table, description, created_at FROM transactions WHERE date >= ? AND date <= ? ORDER BY date DESC, id DESC");
    $stmt->bind_param("ss", $fy['start_date'], $fy['end_date']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    echo json_encode($transactions);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
