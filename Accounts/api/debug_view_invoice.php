<?php
header('Content-Type: text/plain');
require_once 'config.php';

$invoiceNo = $_GET['invoiceNo'] ?? '';

echo "=== DEBUG VIEW INVOICE ===\n\n";
echo "Requested Invoice No: $invoiceNo\n\n";

if (!$invoiceNo) {
    die('Invoice number required');
}

try {
    // Fetch invoice from database
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
            c.id as customer_id,
            c.name as billToName,
            c.phone,
            c.email,
            c.gst_number as gstNumber
        FROM invoices i
        JOIN customers c ON i.customer_id = c.id
        WHERE i.invoice_no = ?
    ");
    $stmt->bind_param("s", $invoiceNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "ERROR: Invoice not found in database!\n\n";
        
        // Show all invoices for debugging
        echo "All invoices in database:\n";
        $allInvoices = $conn->query("SELECT invoice_no, type, created_at FROM invoices ORDER BY created_at DESC LIMIT 10");
        while ($row = $allInvoices->fetch_assoc()) {
            echo "  - {$row['invoice_no']} ({$row['type']}) - {$row['created_at']}\n";
        }
        exit;
    }
    
    $invoice = $result->fetch_assoc();
    
    echo "Invoice Found!\n";
    echo "================\n";
    echo "ID: {$invoice['id']}\n";
    echo "Invoice No: {$invoice['invoice_no']}\n";
    echo "Type: {$invoice['type']}\n";
    echo "Customer ID: {$invoice['customer_id']}\n";
    echo "Customer Name: {$invoice['billToName']}\n";
    echo "Phone: {$invoice['phone']}\n";
    echo "Email: {$invoice['email']}\n";
    echo "GST Number: {$invoice['gstNumber']}\n";
    echo "Invoice Date: {$invoice['invoice_date']}\n";
    echo "Created At: {$invoice['created_at']}\n";
    echo "Original Total Payable: {$invoice['original_total_payable']}\n";
    echo "Cumulative Total Paid: {$invoice['cumulative_total_paid']}\n";
    echo "\nItems JSON:\n{$invoice['items']}\n";
    
    echo "\n\nParsed Items:\n";
    $items = json_decode($invoice['items'], true);
    print_r($items);
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
