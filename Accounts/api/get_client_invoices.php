<?php
header('Content-Type: application/json');
require_once 'config.php';

$phone = $_GET['phone'] ?? '';
$type = $_GET['type'] ?? '';

if (!$phone) {
    echo json_encode(['error' => 'Phone number required']);
    exit;
}

try {
    // Get client
    $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([]);
        exit;
    }
    
    $client = $result->fetch_assoc();
    $clientId = $client['id'];
    
    // Get invoices
    if ($type) {
        $stmt = $conn->prepare("
            SELECT 
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
            WHERE i.client_id = ? AND i.type = ?
            ORDER BY i.created_at ASC
        ");
        $stmt->bind_param("is", $clientId, $type);
    } else {
        $stmt = $conn->prepare("
            SELECT 
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
            WHERE i.client_id = ?
            ORDER BY i.created_at ASC
        ");
        $stmt->bind_param("i", $clientId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invoices[] = [
            'invoiceNo' => $row['invoice_no'],
            'type' => $row['type'],
            'items' => $row['items'],
            'originalTotalPayable' => $row['original_total_payable'],
            'cumulativeTotalPaid' => $row['cumulative_total_paid'],
            'billToName' => $row['billToName'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'gstNumber' => $row['gstNumber'],
            'date' => $row['invoice_date'],
            'generatedAt' => $row['created_at']
        ];
    }
    
    echo json_encode($invoices);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
