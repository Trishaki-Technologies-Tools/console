<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log("save_invoice.php - Received data: " . json_encode($data));

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$billToName = $data['billToName'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$gstNumber = $data['gstNumber'] ?? '';
$type = $data['type'] ?? 'non-gst';
$items = $data['items'] ?? '[]';
$invoiceDate = $data['date'] ?? date('Y-m-d');
$continueFrom = $data['continueFrom'] ?? null;
$originalTotalPayable = $data['originalTotalPayable'] ?? null;
$cumulativeTotalPaid = $data['cumulativeTotalPaid'] ?? null;

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if customer exists
    $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Customer exists, get ID
        $customer = $result->fetch_assoc();
        $customerId = $customer['id'];
        
        // Update customer info
        $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, gst_number = ? WHERE id = ?");
        $stmt->bind_param("sssi", $billToName, $email, $gstNumber, $customerId);
        $stmt->execute();
    } else {
        // Create new customer
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, email, gst_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $billToName, $phone, $email, $gstNumber);
        $stmt->execute();
        $customerId = $conn->insert_id;
    }
    
    // Generate invoice number
    $invoiceNo = generateInvoiceNumber($conn, $customerId, $type, $continueFrom, $items);
    
    // Insert invoice
    $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, customer_id, type, items, original_total_payable, cumulative_total_paid, invoice_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissdds", $invoiceNo, $customerId, $type, $items, $originalTotalPayable, $cumulativeTotalPaid, $invoiceDate);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'invoiceNo' => $invoiceNo,
        'customerId' => $customerId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function generateInvoiceNumber($conn, $customerId, $type, $continueFrom, $items) {
    $year = date('Y');
    
    // Debug logging
    error_log("generateInvoiceNumber - customerId: $customerId, type: $type, continueFrom: " . ($continueFrom ?? 'null'));
    
    // Check if current invoice is a partial payment
    $itemsArray = json_decode($items, true);
    $currentTotalPayable = 0;
    $currentTotalPaid = 0;
    
    foreach ($itemsArray as $item) {
        $currentTotalPayable += floatval($item['totalInclTax'] ?? $item['amount'] ?? 0);
        $currentTotalPaid += floatval($item['paidAmt'] ?? $item['amount'] ?? 0);
    }
    
    $isPartialPayment = $currentTotalPayable > $currentTotalPaid;
    error_log("generateInvoiceNumber - totalPayable: $currentTotalPayable, totalPaid: $currentTotalPaid, isPartial: " . ($isPartialPayment ? 'yes' : 'no'));
    
    // If continuing from existing invoice (user clicked "Continue Part Payment")
    if ($continueFrom) {
        // Extract base invoice number and increment payment number
        if (preg_match('/^(TSK-\d{4}-\d{3})(?:\/P(\d+))?$/', $continueFrom, $matches)) {
            $baseInvoice = $matches[1];
            $paymentNum = isset($matches[2]) ? intval($matches[2]) + 1 : 2;
            $newInvoiceNo = $baseInvoice . '/P' . $paymentNum;
            error_log("generateInvoiceNumber - Continuing from $continueFrom, new invoice: $newInvoiceNo");
            return $newInvoiceNo;
        }
    }
    
    // Generate new invoice number - count distinct base invoice numbers
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT 
            CASE 
                WHEN invoice_no LIKE '%/P%' THEN SUBSTRING_INDEX(invoice_no, '/P', 1)
                ELSE invoice_no
            END
        ) as count 
        FROM invoices 
        WHERE invoice_no LIKE ?
    ");
    $pattern = "TSK-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nextNumber = $row['count'] + 1;
    
    error_log("generateInvoiceNumber - Base invoice count: {$row['count']}, next number: $nextNumber");
    
    $baseInvoiceNo = 'TSK-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    
    // If this is a partial payment, add /P1
    if ($isPartialPayment) {
        $finalInvoiceNo = $baseInvoiceNo . '/P1';
        error_log("generateInvoiceNumber - New partial payment invoice: $finalInvoiceNo");
        return $finalInvoiceNo;
    }
    
    error_log("generateInvoiceNumber - New full payment invoice: $baseInvoiceNo");
    return $baseInvoiceNo;
}
?>
