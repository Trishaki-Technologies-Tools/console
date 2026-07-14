<?php
require_once 'config.php';

$token = $_GET['token'] ?? '';
$receiptNo = $_GET['receiptNo'] ?? '';

if ($token) {
    $receiptNo = decryptToken($token);
}

// Debug logging
error_log("view_receipt.php - Requested token: $token, receipt: $receiptNo");

if (!$receiptNo) {
    die('Receipt identifier required or invalid link');
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
    
    // Populate $_GET parameters so that generate_receipt.php can read them directly
    $_GET['type'] = $receipt['type'];
    $_GET['billToName'] = $receipt['billToName'];
    $_GET['phone'] = $receipt['phone'];
    $_GET['email'] = $receipt['email'];
    $_GET['gstNumber'] = $receipt['gstNumber'];
    $_GET['address'] = '';
    $_GET['receiptNo'] = $receipt['receipt_no'];
    $_GET['invoice_no'] = $receipt['invoice_no'] ?? '';
    $_GET['items'] = $receipt['items'];
    $_GET['date'] = $receipt['receipt_date'];
    $_GET['originalTotalPayable'] = $receipt['original_total_payable'];
    $_GET['cumulativeTotalPaid'] = $receipt['cumulative_total_paid'];
    
    // Render generate_receipt.php inline to mask the URL
    include 'generate_receipt.php';
    exit;
    
} catch (Exception $e) {
    error_log("view_receipt.php - Error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>
