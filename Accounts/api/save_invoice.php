<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'invoice_utils.php';

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
$address = $data['address'] ?? '';
$type = $data['type'] ?? 'non-gst';
$items = $data['items'] ?? '[]';
$invoiceDateInput = $data['date'] ?? '';
$continueFrom = $data['continueFrom'] ?? null;
$originalTotalPayableInput = !empty($data['originalTotalPayable']) ? floatval($data['originalTotalPayable']) : null;
$cumulativeTotalPaid = !empty($data['cumulativeTotalPaid']) ? floatval($data['cumulativeTotalPaid']) : 0;
$editInvoiceNo = !empty($data['invoiceNo']) ? trim($data['invoiceNo']) : null;

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if client exists
    $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Client exists, get ID
        $client = $result->fetch_assoc();
        $clientId = $client['id'];
        
        // Update client info
        $stmt = $conn->prepare("UPDATE clients SET name = ?, email = ?, gst_number = ? WHERE id = ?");
        $stmt->bind_param("sssi", $billToName, $email, $gstNumber, $clientId);
        $stmt->execute();
    } else {
        // Create new client
        $stmt = $conn->prepare("INSERT INTO clients (name, phone, email, gst_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $billToName, $phone, $email, $gstNumber);
        $stmt->execute();
        $clientId = $conn->insert_id;
    }
    
    // Check if we are in edit mode
    $isEditMode = false;
    $existingInvoiceId = null;
    if ($editInvoiceNo) {
        $stmt = $conn->prepare("SELECT id FROM invoices WHERE invoice_no = ?");
        $stmt->bind_param("s", $editInvoiceNo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $existingInvoiceId = $res->fetch_assoc()['id'];
            $isEditMode = true;
            $invoiceNo = $editInvoiceNo;
        }
    }

    if (!$isEditMode) {
        // Generate new invoice number
        $invoiceNo = generateInvoiceNumber($conn, $clientId, $type, $continueFrom, $items);
    }
    
    // Calculate totals from items
    $paidItems = json_decode($items, true);
    $currentPaid = 0;
    $calcTotal = 0;
    $itemDate = null;
    if (is_array($paidItems)) {
        if (!empty($paidItems) && isset($paidItems[0]['date'])) {
            $itemDate = $paidItems[0]['date'];
        }
        foreach ($paidItems as $item) {
            $currentPaid += floatval($item['paidAmt'] ?? $item['amount'] ?? 0);
            if ($type === 'gst') {
                $calcTotal += floatval($item['totalInclTax'] ?? $item['amount'] ?? 0);
            } else {
                $calcTotal += floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
            }
        }
    }
    
    $invoiceDate = !empty($invoiceDateInput) ? $invoiceDateInput : (!empty($itemDate) ? $itemDate : date('Y-m-d'));
    
    $originalTotalPayable = ($originalTotalPayableInput !== null && $originalTotalPayableInput > 0)
        ? $originalTotalPayableInput
        : ($calcTotal > 0 ? $calcTotal : 5000.00);

    // Extract base invoice prefix to identify continuation group
    $baseInvoice = null;
    if (preg_match('/^(TSK-\d{4}-\d{3})/', $invoiceNo, $matches)) {
        $baseInvoice = $matches[1];
    }

    // Find previous sum for the continuation group if continuing or editing
    $prevSum = 0;
    if ($isEditMode) {
        if ($baseInvoice) {
            $stmt = $conn->prepare("
                SELECT items 
                FROM invoices 
                WHERE invoice_no LIKE ? AND id < ?
            ");
            $pattern = $baseInvoice . "%";
            $stmt->bind_param("si", $pattern, $existingInvoiceId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($invRow = $res->fetch_assoc()) {
                $invItems = json_decode($invRow['items'], true);
                if (is_array($invItems)) {
                    foreach ($invItems as $itm) {
                        $prevSum += floatval($itm['paidAmt'] ?? $itm['amount'] ?? 0);
                    }
                }
            }
        }
    } else {
        if ($continueFrom) {
            if (preg_match('/^(TSK-\d{4}-\d{3})/', $continueFrom, $matches)) {
                $baseInvoice = $matches[1];
                $stmt = $conn->prepare("
                    SELECT items 
                    FROM invoices 
                    WHERE invoice_no LIKE ?
                ");
                $pattern = $baseInvoice . "%";
                $stmt->bind_param("s", $pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($invRow = $res->fetch_assoc()) {
                    $invItems = json_decode($invRow['items'], true);
                    if (is_array($invItems)) {
                        foreach ($invItems as $itm) {
                            $prevSum += floatval($itm['paidAmt'] ?? $itm['amount'] ?? 0);
                        }
                    }
                }
            }
        }
    }
    $totalCumulative = $prevSum + $currentPaid;
    
    // Status calculation
    $status = 'unpaid';
    if ($totalCumulative >= $originalTotalPayable - 0.01) {
        $status = 'paid';
    } elseif ($totalCumulative > 0) {
        $status = 'partially_paid';
    }

    if ($isEditMode) {
        // Update existing invoice row
        $stmt = $conn->prepare("UPDATE invoices SET client_id = ?, type = ?, items = ?, original_total_payable = ?, cumulative_total_paid = ?, invoice_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("issddssi", $clientId, $type, $items, $originalTotalPayable, $totalCumulative, $invoiceDate, $status, $existingInvoiceId);
        $stmt->execute();

        // Cascade update to any subsequent installments in the same continuation group
        if ($baseInvoice) {
            $stmt = $conn->prepare("SELECT id, items, original_total_payable FROM invoices WHERE invoice_no LIKE ? AND id > ? ORDER BY id ASC");
            $pattern = $baseInvoice . "%";
            $stmt->bind_param("si", $pattern, $existingInvoiceId);
            $stmt->execute();
            $subsequentResult = $stmt->get_result();
            $runningCumulative = $totalCumulative;
            while ($subInv = $subsequentResult->fetch_assoc()) {
                $subItems = json_decode($subInv['items'], true);
                $subPaid = 0;
                if (is_array($subItems)) {
                    foreach ($subItems as $subItem) {
                        $subPaid += floatval($subItem['paidAmt'] ?? $subItem['amount'] ?? 0);
                    }
                }
                $runningCumulative += $subPaid;
                
                $subStatus = 'unpaid';
                $subOrig = floatval($subInv['original_total_payable']);
                if ($runningCumulative >= $subOrig - 0.01) {
                    $subStatus = 'paid';
                } elseif ($runningCumulative > 0) {
                    $subStatus = 'partially_paid';
                }
                
                $updateStmt = $conn->prepare("UPDATE invoices SET cumulative_total_paid = ?, status = ? WHERE id = ?");
                $updateStmt->bind_param("dsi", $runningCumulative, $subStatus, $subInv['id']);
                $updateStmt->execute();
            }
        }
    } else {
        // Insert new invoice row
        $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, client_id, type, items, original_total_payable, cumulative_total_paid, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissddss", $invoiceNo, $clientId, $type, $items, $originalTotalPayable, $totalCumulative, $invoiceDate, $status);
        $stmt->execute();
    }
    
    // Automatically generate/update receipt if there is an amount paid
    if ($currentPaid > 0) {
        require_once 'receipt_utils.php';
        
        $receiptItems = json_encode([[
            'description' => "Payment for Invoice #$invoiceNo",
            'amount' => $currentPaid,
            'paidAmt' => $currentPaid,
            'paymentMode' => $paidItems[0]['paymentMode'] ?? 'Online',
            'date' => $invoiceDate
        ]]);
        
        $rStatus = ($currentPaid >= $originalTotalPayable - 0.01) ? 'paid' : 'partially_paid';
        
        if ($isEditMode) {
            // Check if there is an existing receipt for this invoice
            $chk = $conn->prepare("SELECT id FROM receipts WHERE invoice_no = ? ORDER BY id ASC LIMIT 1");
            $chk->bind_param("s", $invoiceNo);
            $chk->execute();
            $chkRes = $chk->get_result();
            if ($chkRes->num_rows > 0) {
                $existingRec = $chkRes->fetch_assoc();
                // Update first receipt
                $stmtRec = $conn->prepare("UPDATE receipts SET client_id = ?, type = ?, items = ?, original_total_payable = ?, cumulative_total_paid = ?, receipt_date = ?, status = ? WHERE id = ?");
                $stmtRec->bind_param("issddssi", $clientId, $type, $receiptItems, $originalTotalPayable, $currentPaid, $invoiceDate, $rStatus, $existingRec['id']);
                $stmtRec->execute();
            } else {
                // Create a new one
                $receiptNo = generateReceiptNumber($conn, $clientId, $type, null, $receiptItems);
                $stmtRec = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtRec->bind_param("sisssddss", $receiptNo, $clientId, $invoiceNo, $type, $receiptItems, $originalTotalPayable, $currentPaid, $invoiceDate, $rStatus);
                $stmtRec->execute();
            }
        } else {
            // New invoice, insert receipt
            $receiptNo = generateReceiptNumber($conn, $clientId, $type, null, $receiptItems);
            $stmtRec = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtRec->bind_param("sisssddss", $receiptNo, $clientId, $invoiceNo, $type, $receiptItems, $originalTotalPayable, $currentPaid, $invoiceDate, $rStatus);
            $stmtRec->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'invoiceNo' => $invoiceNo,
        'clientId' => $clientId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
