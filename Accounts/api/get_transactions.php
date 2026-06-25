<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $query = "SELECT id, type, amount, date, reference_id, reference_table, description, created_at FROM transactions ORDER BY date DESC, id DESC LIMIT 500";
    $result = $conn->query($query);
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    echo json_encode($transactions);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
