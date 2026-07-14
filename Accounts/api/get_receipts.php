<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            r.id,
            r.receipt_no,
            r.type,
            r.items,
            r.original_total_payable,
            r.cumulative_total_paid,
            r.receipt_date,
            r.created_at,
            c.name as billToName,
            c.phone,
            c.email,
            c.gst_number as gstNumber
        FROM receipts r
        JOIN clients c ON r.client_id = c.id
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $receipts = [];
    while ($row = $result->fetch_assoc()) {
        $receipts[] = [
            'id' => $row['id'],
            'receiptNo' => $row['receipt_no'],
            'token' => encryptToken($row['receipt_no']),
            'type' => $row['type'],
            'items' => $row['items'],
            'originalTotalPayable' => $row['original_total_payable'],
            'cumulativeTotalPaid' => $row['cumulative_total_paid'],
            'billToName' => $row['billToName'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'gstNumber' => $row['gstNumber'],
            'address' => '', // client has no address field in new schema
            'date' => $row['receipt_date'],
            'generatedAt' => $row['created_at']
        ];
    }
    
    echo json_encode($receipts);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
