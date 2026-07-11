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
    
    // Get receipts
    if ($type) {
        $stmt = $conn->prepare("
            SELECT 
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
            WHERE r.client_id = ? AND r.type = ?
            ORDER BY r.created_at ASC
        ");
        $stmt->bind_param("is", $clientId, $type);
    } else {
        $stmt = $conn->prepare("
            SELECT 
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
            WHERE r.client_id = ?
            ORDER BY r.created_at ASC
        ");
        $stmt->bind_param("i", $clientId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $receipts = [];
    while ($row = $result->fetch_assoc()) {
        $receipts[] = [
            'receiptNo' => $row['receipt_no'],
            'type' => $row['type'],
            'items' => $row['items'],
            'originalTotalPayable' => $row['original_total_payable'],
            'cumulativeTotalPaid' => $row['cumulative_total_paid'],
            'billToName' => $row['billToName'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'gstNumber' => $row['gstNumber'],
            'date' => $row['receipt_date'],
            'generatedAt' => $row['created_at']
        ];
    }
    
    echo json_encode($receipts);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
