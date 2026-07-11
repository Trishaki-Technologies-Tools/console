<?php
// CLI script to import interns payments CSV and generate invoices + receipts
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/invoice_utils.php';
require_once __DIR__ . '/../api/receipt_utils.php';

$csvFile = __DIR__ . '/../api/interns_payments_march.csv';
if (!file_exists($csvFile)) {
    die("CSV file not found at: $csvFile\n");
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Unable to open CSV file.\n");
}

// Skip header
$headers = fgetcsv($handle);

$count = 0;
$conn->begin_transaction();

try {
    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue; // Skip empty rows

        $name = trim($row[0]);
        $phone = trim($row[1]);
        $email = trim($row[2]);
        $desc = trim($row[3]);
        $mode = trim($row[4]);
        $date = trim($row[5]);
        $totalCharged = floatval($row[6]);
        $paidNow = floatval($row[7]);
        $type = trim($row[8]); // non-gst

        // 1. Check/Insert Client
        $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $clientId = $res->fetch_assoc()['id'];
            $uStmt = $conn->prepare("UPDATE clients SET name = ?, email = ? WHERE id = ?");
            $uStmt->bind_param("ssi", $name, $email, $clientId);
            $uStmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $phone, $email);
            $stmt->execute();
            $clientId = $conn->insert_id;
        }

        // 2. Create Invoice
        $invoiceItemsArr = [[
            'description' => $desc,
            'amount' => $totalCharged,
            'totalInclTax' => $totalCharged,
            'gst' => 0,
            'charges' => $totalCharged
        ]];
        $invoiceItemsJson = json_encode($invoiceItemsArr);
        $invoiceNo = generateInvoiceNumber($conn, $clientId, $type, null, $invoiceItemsJson);

        $invoiceStatus = ($paidNow >= $totalCharged - 0.01) ? 'paid' : (($paidNow > 0) ? 'partially_paid' : 'unpaid');
        $insInv = $conn->prepare("INSERT INTO invoices (invoice_no, client_id, type, items, original_total_payable, cumulative_total_paid, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insInv->bind_param("sissddss", $invoiceNo, $clientId, $type, $invoiceItemsJson, $totalCharged, $paidNow, $date, $invoiceStatus);
        $insInv->execute();

        // 3. Create Receipt
        $itemsArr = [[
            'description' => $desc,
            'paymentMode' => $mode,
            'date' => $date,
            'amount' => $totalCharged,
            'totalInclTax' => $totalCharged,
            'paidAmt' => $paidNow,
            'tax' => 0, 
            'charges' => $totalCharged 
        ]];
        $itemsJson = json_encode($itemsArr);

        // Generate receipt number (check if client had any receipt before, but since we are inserting the first one it's null)
        $receiptNo = generateReceiptNumber($conn, $clientId, $type, null, $itemsJson);

        $receiptStatus = ($paidNow >= $totalCharged - 0.01) ? 'paid' : 'partially_paid';

        $insRec = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insRec->bind_param("sisssddss", $receiptNo, $clientId, $invoiceNo, $type, $itemsJson, $totalCharged, $paidNow, $date, $receiptStatus);
        $insRec->execute();

        $count++;
        echo "Row $count: Created client $name, Invoice $invoiceNo, Receipt $receiptNo.\n";
    }

    $conn->commit();
    echo "\nSuccessfully processed and committed $count rows!\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "ERROR: " . $e->getMessage() . "\n";
}

fclose($handle);
?>
