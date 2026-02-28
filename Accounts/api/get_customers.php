<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.name,
            c.phone,
            c.email,
            c.gst_number,
            COUNT(i.id) as invoice_count,
            MAX(i.created_at) as last_invoice_date
        FROM customers c
        LEFT JOIN invoices i ON c.id = i.customer_id
        GROUP BY c.id
        ORDER BY last_invoice_date DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'email' => $row['email'] ?: 'N/A',
            'gstNumber' => $row['gst_number'] ?: 'Not Applicable',
            'invoiceCount' => $row['invoice_count'],
            'lastInvoiceDate' => $row['last_invoice_date']
        ];
    }
    
    echo json_encode($customers);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
