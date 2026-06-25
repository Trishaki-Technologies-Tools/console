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
    
    // Redirect to generate_invoice.php with all parameters
    $params = http_build_query([
        'type' => $invoice['type'],
        'billToName' => $invoice['billToName'],
        'phone' => $invoice['phone'],
        'email' => $invoice['email'],
        'gstNumber' => $invoice['gstNumber'],
        'address' => '',
        'invoiceNo' => $invoice['invoice_no'],
        'items' => $invoice['items'],
        'date' => $invoice['invoice_date'],
        'originalTotalPayable' => $invoice['original_total_payable'],
        'cumulativeTotalPaid' => $invoice['cumulative_total_paid']
    ]);
    
    header("Location: generate_invoice.php?$params");
    exit;
    
} catch (Exception $e) {
    error_log("view_invoice.php - Error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>
