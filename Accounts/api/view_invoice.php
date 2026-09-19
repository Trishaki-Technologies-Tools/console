<?php
require_once 'config.php';

$invoiceNo = trim($_GET['invoiceNo'] ?? ($_GET['invoice_no'] ?? ''));
$id = !empty($_GET['id']) ? intval($_GET['id']) : null;
$type = $_GET['type'] ?? '';

// Debug logging
error_log("view_invoice.php - Requested invoice id: $id, no: $invoiceNo (type: $type)");

if (!$id && !$invoiceNo) {
    die('Invoice identifier (id or invoiceNo) required');
}

try {
    if ($id) {
        $stmt = $conn->prepare("
            SELECT 
                i.id,
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
            WHERE i.id = ?
        ");
        $stmt->bind_param("i", $id);
    } elseif ($type) {
        $stmt = $conn->prepare("
            SELECT 
                i.id,
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
            WHERE i.invoice_no = ? AND LOWER(i.type) = LOWER(?)
            ORDER BY i.id DESC LIMIT 1
        ");
        $stmt->bind_param("ss", $invoiceNo, $type);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                i.id,
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
            ORDER BY i.id DESC LIMIT 1
        ");
        $stmt->bind_param("s", $invoiceNo);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        error_log("view_invoice.php - Invoice not found: id=$id, no=$invoiceNo");
        die('Bill / Invoice not found.');
    }
    
    $invoice = $result->fetch_assoc();
    
    // Redirect to generate_invoice.php with all parameters
    $params = http_build_query([
        'id' => $invoice['id'],
        'type' => $invoice['type'],
        'billToName' => $invoice['billToName'],
        'phone' => $invoice['phone'],
        'email' => $invoice['email'],
        'gstNumber' => $invoice['gstNumber'],
        'address' => '',
        'invoiceNo' => $invoice['invoice_no'] ?? '',
        'items' => $invoice['items'],
        'date' => $invoice['invoice_date'],
        'originalTotalPayable' => $invoice['original_total_payable'],
        'cumulativeTotalPaid' => $invoice['cumulative_total_paid']
    ]);
    
    log_action($conn, 'VIEW', 'invoices', $invoice['id'], "Viewed bill/invoice: #" . ($invoice['invoice_no'] ?: $invoice['id']));
    
    header("Location: generate_invoice.php?$params");
    exit;
    
} catch (Exception $e) {
    error_log("view_invoice.php - Error: " . $e->getMessage());
    die('Error: ' . $e->getMessage());
}
?>
