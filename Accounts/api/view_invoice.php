<?php
require_once 'config.php';

$invoiceNo = $_GET['invoiceNo'] ?? '';

// Debug logging
error_log("view_invoice.php - Requested invoice: $invoiceNo");

if (!$invoiceNo) {
    die('Invoice number required');
}

try {
    // Fetch invoice from database
    $stmt = $conn->prepare("
        SELECT 
            i.invoice_no,
            i.type,
            i.items,
            i.original_total_payable,
            i.cumulative_total_paid,
            i.invoice_date,
            c.name as billToName,
            c.phone,
            c.email,
            c.gst_number as gstNumber
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        WHERE i.invoice_no = ?
    ");
    $stmt->bind_param("s", $invoiceNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("view_invoice.php - Invoice not found: $invoiceNo");
        die('Invoice not found: ' . htmlspecialchars($invoiceNo));
    }
    
    $invoice = $result->fetch_assoc();
    
    error_log("view_invoice.php - Found invoice: {$invoice['invoice_no']} for client: {$invoice['billToName']}");
    
    // Populate $_GET parameters so that generate_invoice.php can read them directly
    $_GET['type'] = $invoice['type'];
    $_GET['billToName'] = $invoice['billToName'];
    $_GET['phone'] = $invoice['phone'];
    $_GET['email'] = $invoice['email'];
    $_GET['gstNumber'] = $invoice['gstNumber'];
    $_GET['address'] = '';
    $_GET['invoiceNo'] = $invoice['invoice_no'];
    $_GET['items'] = $invoice['items'];
    $_GET['date'] = $invoice['invoice_date'];
    $_GET['originalTotalPayable'] = $invoice['original_total_payable'];
    $_GET['cumulativeTotalPaid'] = $invoice['cumulative_total_paid'];
    
    // Render generate_invoice.php inline to mask the URL
    include 'generate_invoice.php';
    exit;
    
} catch (Exception $e) {
    error_log("view_invoice.php - Error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>
