<?php
header('Content-Type: text/plain');
require_once 'config.php';

$phone = $_GET['phone'] ?? '';
$type = $_GET['type'] ?? 'non-gst';

echo "=== TESTING INVOICE NUMBER GENERATION ===\n\n";
echo "Phone: $phone\n";
echo "Type: $type\n\n";

// Get customer
$stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Customer not found!\n");
}

$customer = $result->fetch_assoc();
$customerId = $customer['id'];
echo "Customer ID: $customerId\n\n";

// Check for existing invoices
$stmt = $conn->prepare("
    SELECT invoice_no, items, created_at
    FROM invoices 
    WHERE customer_id = ? AND type = ? 
    ORDER BY created_at DESC
");
$stmt->bind_param("is", $customerId, $type);
$stmt->execute();
$result = $stmt->get_result();

echo "Existing Invoices:\n";
echo "==================\n";

$foundDue = false;
while ($row = $result->fetch_assoc()) {
    echo "\nInvoice: {$row['invoice_no']}\n";
    echo "Created: {$row['created_at']}\n";
    echo "Items JSON: {$row['items']}\n";
    
    $items = json_decode($row['items'], true);
    $totalPayable = 0;
    $totalPaid = 0;
    
    foreach ($items as $item) {
        $tp = floatval($item['totalInclTax'] ?? $item['amount'] ?? 0);
        $paid = floatval($item['paidAmt'] ?? $item['amount'] ?? 0);
        echo "  Item: totalPayable=$tp, paid=$paid\n";
        $totalPayable += $tp;
        $totalPaid += $paid;
    }
    
    echo "  Total Payable: $totalPayable\n";
    echo "  Total Paid: $totalPaid\n";
    echo "  Has Due: " . ($totalPayable > $totalPaid ? 'YES' : 'NO') . "\n";
    
    if ($totalPayable > $totalPaid && !$foundDue) {
        $foundDue = true;
        echo "  >>> This invoice has due! Next invoice should be: {$row['invoice_no']}/P1\n";
    }
}

if (!$foundDue) {
    echo "\nNo invoices with due amount found.\n";
    echo "Next invoice will be a new sequential number.\n";
}

// Test what the actual function would return
echo "\n\n=== SIMULATING generateInvoiceNumber() ===\n";

function testGenerateInvoiceNumber($conn, $customerId, $type, $continueFrom) {
    $year = date('Y');
    
    if ($continueFrom) {
        if (preg_match('/^(TSK-\d{4}-\d{3})(?:\/P(\d+))?$/', $continueFrom, $matches)) {
            $baseInvoice = $matches[1];
            $paymentNum = isset($matches[2]) ? intval($matches[2]) + 1 : 2;
            return $baseInvoice . '/P' . $paymentNum;
        }
    }
    
    $stmt = $conn->prepare("
        SELECT invoice_no, items 
        FROM invoices 
        WHERE customer_id = ? AND type = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("is", $customerId, $type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items = json_decode($row['items'], true);
        $totalPayable = 0;
        $totalPaid = 0;
        
        foreach ($items as $item) {
            $totalPayable += floatval($item['totalInclTax'] ?? $item['amount'] ?? 0);
            $totalPaid += floatval($item['paidAmt'] ?? $item['amount'] ?? 0);
        }
        
        if ($totalPayable > $totalPaid) {
            if (preg_match('/^(TSK-\d{4}-\d{3})(?:\/P(\d+))?$/', $row['invoice_no'], $matches)) {
                $baseInvoice = $matches[1];
                return $baseInvoice . '/P1';
            }
        }
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM invoices WHERE invoice_no LIKE ?");
    $pattern = "TSK-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nextNumber = $row['count'] + 1;
    
    return 'TSK-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

$nextInvoiceNo = testGenerateInvoiceNumber($conn, $customerId, $type, null);
echo "Next Invoice Number: $nextInvoiceNo\n";
?>
