<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'receipt_utils.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log("save_receipt.php - Received data: " . json_encode($data));

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
$receiptDateInput = $data['date'] ?? '';
$continueFrom = $data['continueFrom'] ?? null;
$originalTotalPayableInput = !empty($data['originalTotalPayable']) ? floatval($data['originalTotalPayable']) : null;
$cumulativeTotalPaid = !empty($data['cumulativeTotalPaid']) ? floatval($data['cumulativeTotalPaid']) : 0;
$editReceiptNo = !empty($data['receiptNo']) ? trim($data['receiptNo']) : null;
$invoiceNo = !empty($data['invoice_no']) ? trim($data['invoice_no']) : (!empty($data['invoiceNo']) ? trim($data['invoiceNo']) : null);

try {
    // Start transaction
    $conn->begin_transaction();
    
    if ($invoiceNo) {
        // Load invoice and client info
        $invStmt = $conn->prepare("
            SELECT i.client_id, i.type, i.original_total_payable, i.cumulative_total_paid,
                   c.name, c.phone, c.email, c.gst_number
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE i.invoice_no = ?
        ");
        $invStmt->bind_param("s", $invoiceNo);
        $invStmt->execute();
        $invRes = $invStmt->get_result();
        if ($invRes->num_rows === 0) {
            throw new Exception("Invoice not found: " . $invoiceNo);
        }
        $invRow = $invRes->fetch_assoc();
        $clientId = $invRow['client_id'];
        $billToName = $invRow['name'];
        $phone = $invRow['phone'];
        $email = $invRow['email'];
        $gstNumber = $invRow['gst_number'];
        $type = $invRow['type'];
        $originalTotalPayableInput = floatval($invRow['original_total_payable']);
        
        // Find if there is a previous receipt for this invoice
        $chkRec = $conn->prepare("SELECT receipt_no FROM receipts WHERE invoice_no = ? ORDER BY id DESC LIMIT 1");
        $chkRec->bind_param("s", $invoiceNo);
        $chkRec->execute();
        $chkRecRes = $chkRec->get_result();
        if ($chkRecRes->num_rows > 0) {
            $continueFrom = $chkRecRes->fetch_assoc()['receipt_no'];
        } else {
            $continueFrom = null;
        }
        
        // Update client info
        $stmt = $conn->prepare("UPDATE clients SET name = ?, email = ?, gst_number = ? WHERE id = ?");
        $stmt->bind_param("sssi", $billToName, $email, $gstNumber, $clientId);
        $stmt->execute();
    } else {
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
    }
    
    // Check if we are in edit mode
    $isEditMode = false;
    $existingReceiptId = null;
    if ($editReceiptNo) {
        $stmt = $conn->prepare("SELECT id FROM receipts WHERE receipt_no = ?");
        $stmt->bind_param("s", $editReceiptNo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $existingReceiptId = $res->fetch_assoc()['id'];
            $isEditMode = true;
            $receiptNo = $editReceiptNo;
        }
    }

    if (!$isEditMode) {
        // Generate new receipt number
        $receiptNo = generateReceiptNumber($conn, $clientId, $type, $continueFrom, $items);
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
    
    $receiptDate = !empty($receiptDateInput) ? $receiptDateInput : (!empty($itemDate) ? $itemDate : date('Y-m-d'));
    
    $originalTotalPayable = ($originalTotalPayableInput !== null && $originalTotalPayableInput > 0)
        ? $originalTotalPayableInput
        : ($calcTotal > 0 ? $calcTotal : 5000.00);

    // Extract base receipt prefix to identify continuation group
    $baseReceipt = null;
    if (preg_match('/^((?:TSK-REC|RECP)-\d{4}-\d{3})/', $receiptNo, $matches)) {
        $baseReceipt = $matches[1];
    }

    // Find previous sum for the continuation group if continuing or editing
    $prevSum = 0;
    if ($invoiceNo) {
        if ($isEditMode) {
            $stmt = $conn->prepare("
                SELECT items 
                FROM receipts 
                WHERE invoice_no = ? AND id < ?
            ");
            $stmt->bind_param("si", $invoiceNo, $existingReceiptId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($recRow = $res->fetch_assoc()) {
                $recItems = json_decode($recRow['items'], true);
                if (is_array($recItems)) {
                    foreach ($recItems as $itm) {
                        $prevSum += floatval($itm['paidAmt'] ?? $itm['amount'] ?? 0);
                    }
                }
            }
        } else {
            $stmt = $conn->prepare("
                SELECT items 
                FROM receipts 
                WHERE invoice_no = ?
            ");
            $stmt->bind_param("s", $invoiceNo);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($recRow = $res->fetch_assoc()) {
                $recItems = json_decode($recRow['items'], true);
                if (is_array($recItems)) {
                    foreach ($recItems as $itm) {
                        $prevSum += floatval($itm['paidAmt'] ?? $itm['amount'] ?? 0);
                    }
                }
            }
        }
    } elseif ($isEditMode) {
        if ($baseReceipt) {
            $stmt = $conn->prepare("
                SELECT items 
                FROM receipts 
                WHERE receipt_no LIKE ? AND id < ?
            ");
            $pattern = $baseReceipt . "%";
            $stmt->bind_param("si", $pattern, $existingReceiptId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($recRow = $res->fetch_assoc()) {
                $recItems = json_decode($recRow['items'], true);
                if (is_array($recItems)) {
                    foreach ($recItems as $itm) {
                        $prevSum += floatval($itm['paidAmt'] ?? $itm['amount'] ?? 0);
                    }
                }
            }
        }
    } else {
        if ($continueFrom) {
            if (preg_match('/^((?:TSK-REC|RECP)-\d{4}-\d{3})/', $continueFrom, $matches)) {
                $baseReceipt = $matches[1];
                $stmt = $conn->prepare("
                    SELECT items 
                    FROM receipts 
                    WHERE receipt_no LIKE ?
                ");
                $pattern = $baseReceipt . "%";
                $stmt->bind_param("s", $pattern);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($recRow = $res->fetch_assoc()) {
                    $recItems = json_decode($recRow['items'], true);
                    if (is_array($recItems)) {
                        foreach ($recItems as $itm) {
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
        // Update existing receipt row
        $stmt = $conn->prepare("UPDATE receipts SET client_id = ?, invoice_no = ?, type = ?, items = ?, original_total_payable = ?, cumulative_total_paid = ?, receipt_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("isssddssi", $clientId, $invoiceNo, $type, $items, $originalTotalPayable, $totalCumulative, $receiptDate, $status, $existingReceiptId);
        $stmt->execute();

        // Cascade update to any subsequent installments in the same continuation group
        if ($invoiceNo) {
            $stmt = $conn->prepare("SELECT id, items, original_total_payable FROM receipts WHERE invoice_no = ? AND id > ? ORDER BY id ASC");
            $stmt->bind_param("si", $invoiceNo, $existingReceiptId);
            $stmt->execute();
            $subsequentResult = $stmt->get_result();
            $runningCumulative = $totalCumulative;
            while ($subRec = $subsequentResult->fetch_assoc()) {
                $subItems = json_decode($subRec['items'], true);
                $subPaid = 0;
                if (is_array($subItems)) {
                    foreach ($subItems as $subItem) {
                        $subPaid += floatval($subItem['paidAmt'] ?? $subItem['amount'] ?? 0);
                    }
                }
                $runningCumulative += $subPaid;
                
                $subStatus = 'unpaid';
                $subOrig = floatval($subRec['original_total_payable']);
                if ($runningCumulative >= $subOrig - 0.01) {
                    $subStatus = 'paid';
                } elseif ($runningCumulative > 0) {
                    $subStatus = 'partially_paid';
                }
                
                $updateStmt = $conn->prepare("UPDATE receipts SET cumulative_total_paid = ?, status = ? WHERE id = ?");
                $updateStmt->bind_param("dsi", $runningCumulative, $subStatus, $subRec['id']);
                $updateStmt->execute();
            }
            $totalCumulative = $runningCumulative;
        } elseif ($baseReceipt) {
            $stmt = $conn->prepare("SELECT id, items, original_total_payable FROM receipts WHERE receipt_no LIKE ? AND id > ? ORDER BY id ASC");
            $pattern = $baseReceipt . "%";
            $stmt->bind_param("si", $pattern, $existingReceiptId);
            $stmt->execute();
            $subsequentResult = $stmt->get_result();
            $runningCumulative = $totalCumulative;
            while ($subRec = $subsequentResult->fetch_assoc()) {
                $subItems = json_decode($subRec['items'], true);
                $subPaid = 0;
                if (is_array($subItems)) {
                    foreach ($subItems as $subItem) {
                        $subPaid += floatval($subItem['paidAmt'] ?? $subItem['amount'] ?? 0);
                    }
                }
                $runningCumulative += $subPaid;
                
                $subStatus = 'unpaid';
                $subOrig = floatval($subRec['original_total_payable']);
                if ($runningCumulative >= $subOrig - 0.01) {
                    $subStatus = 'paid';
                } elseif ($runningCumulative > 0) {
                    $subStatus = 'partially_paid';
                }
                
                $updateStmt = $conn->prepare("UPDATE receipts SET cumulative_total_paid = ?, status = ? WHERE id = ?");
                $updateStmt->bind_param("dsi", $runningCumulative, $subStatus, $subRec['id']);
                $updateStmt->execute();
            }
        }
    } else {
        // Insert new receipt row
        $stmt = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssddss", $receiptNo, $clientId, $invoiceNo, $type, $items, $originalTotalPayable, $totalCumulative, $receiptDate, $status);
        $stmt->execute();
    }
    
    // Update invoice table if linked to an invoice
    if ($invoiceNo) {
        $newInvoiceStatus = ($totalCumulative >= $originalTotalPayable - 0.01) ? 'paid' : 'partially_paid';
        $updInv = $conn->prepare("UPDATE invoices SET cumulative_total_paid = ?, status = ? WHERE invoice_no = ?");
        $updInv->bind_param("dss", $totalCumulative, $newInvoiceStatus, $invoiceNo);
        $updInv->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'receiptNo' => $receiptNo,
        'token' => encryptToken($receiptNo),
        'clientId' => $clientId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
