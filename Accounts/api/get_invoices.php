<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            i.id,
            i.invoice_no,
            i.type,
            i.items,
            i.original_total_payable,
            i.cumulative_total_paid,
            i.invoice_date,
            i.created_at,
            c.name as billToName,
            c.phone,
            c.email,
            c.gst_number as gstNumber
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        ORDER BY i.created_at DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = [
            'id' => $row['id'],
            'invoiceNo' => $row['invoice_no'],
            'token' => encryptToken($row['invoice_no']),
            'type' => $row['type'],
            'items' => $row['items'],
            'originalTotalPayable' => $row['original_total_payable'],
            'cumulativeTotalPaid' => $row['cumulative_total_paid'],
            'billToName' => $row['billToName'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'gstNumber' => $row['gstNumber'],
            'address' => '', // client has no address field in new schema
            'date' => $row['invoice_date'],
            'generatedAt' => $row['created_at']
        ];
    }
    
    echo json_encode($invoices);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
