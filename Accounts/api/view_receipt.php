<?php
require_once 'config.php';

$receiptNo = $_GET['receiptNo'] ?? '';

// Debug logging
error_log("view_receipt.php - Requested receipt: $receiptNo");

if (!$receiptNo) {
    die('Receipt number required');
}

try {
    // Fetch receipt from database
    $stmt = $conn->prepare("
        SELECT 
            r.receipt_no,
            r.invoice_no,
            r.type,
            r.items,
            r.original_total_payable,
            r.cumulative_total_paid,
            r.receipt_date,
            c.name as billToName,
            c.phone,
            c.email,
            c.gst_number as gstNumber
        FROM receipts r
        JOIN clients c ON r.client_id = c.id
        WHERE r.receipt_no = ?
    ");
    $stmt->bind_param("s", $receiptNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("view_receipt.php - Receipt not found: $receiptNo");
        die('Receipt not found: ' . htmlspecialchars($receiptNo));
    }
    
    $receipt = $result->fetch_assoc();
    
    error_log("view_receipt.php - Found receipt: {$receipt['receipt_no']} for client: {$receipt['billToName']}");
    
    // Redirect to generate_receipt.php with all parameters
    $params = http_build_query([
        'type' => $receipt['type'],
        'billToName' => $receipt['billToName'],
        'phone' => $receipt['phone'],
        'email' => $receipt['email'],
        'gstNumber' => $receipt['gstNumber'],
        'address' => '',
        'receiptNo' => $receipt['receipt_no'],
        'invoice_no' => $receipt['invoice_no'] ?? '',
        'items' => $receipt['items'],
        'date' => $receipt['receipt_date'],
        'originalTotalPayable' => $receipt['original_total_payable'],
        'cumulativeTotalPaid' => $receipt['cumulative_total_paid']
    ]);
    
    header("Location: generate_receipt.php?$params");
    exit;
    
} catch (Exception $e) {
    error_log("view_receipt.php - Error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>
