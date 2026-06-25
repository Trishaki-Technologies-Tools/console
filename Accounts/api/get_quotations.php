<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            q.id,
            q.quotation_no,
            q.items,
            q.total_amount,
            q.quotation_date,
            q.status,
            q.created_at,
            c.name as client_name,
            c.phone,
            c.email
        FROM quotations q
        JOIN clients c ON q.client_id = c.id
        ORDER BY q.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $quotations = [];
    while ($row = $result->fetch_assoc()) {
        $quotations[] = [
            'id' => $row['id'],
            'quotationNo' => $row['quotation_no'],
            'items' => json_decode($row['items'], true),
            'totalAmount' => $row['total_amount'],
            'date' => $row['quotation_date'],
            'status' => $row['status'],
            'clientName' => $row['client_name'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'createdAt' => $row['created_at']
        ];
    }
    
    echo json_encode($quotations);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
