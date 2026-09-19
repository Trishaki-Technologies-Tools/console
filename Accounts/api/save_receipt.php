<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'invoice_utils.php';
require_once 'receipt_utils.php';
require_once 'settlement_helper.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

ensureReceiptsTableExists($conn);

$billToName = $data['billToName'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$gstNumber = $data['gstNumber'] ?? '';
$address = $data['address'] ?? '';
$type = $data['type'] ?? 'non-gst';
$rawItems = $data['items'] ?? '[]';
$receiptDateInput = $data['receiptDate'] ?? ($data['date'] ?? '');
$continueFrom = $data['continueFrom'] ?? null;
$originalTotalPayableInput = !empty($data['originalTotalPayable']) ? floatval($data['originalTotalPayable']) : null;
$cumulativeTotalPaid = !empty($data['cumulativeTotalPaid']) ? floatval($data['cumulativeTotalPaid']) : 0;
$editId = !empty($data['editId']) ? intval($data['editId']) : null;
$editReceiptNo = !empty($data['receiptNo']) ? trim($data['receiptNo']) : (!empty($data['receipt_no']) ? trim($data['receipt_no']) : null);
$invoiceNo = !empty($data['invoice_no']) ? trim($data['invoice_no']) : (!empty($data['invoiceNo']) ? trim($data['invoiceNo']) : null);
$invoiceId = !empty($data['invoice_id']) ? intval($data['invoice_id']) : (!empty($data['invoiceId']) ? intval($data['invoiceId']) : null);

if (is_array($rawItems)) {
    $itemsJson = json_encode($rawItems);
    $paidItems = $rawItems;
} else {
    $itemsJson = $rawItems;
    $paidItems = json_decode($rawItems, true);
    if (!is_array($paidItems)) {
        $paidItems = [];
    }
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    $targetInvoiceId = $invoiceId;

    if ($targetInvoiceId) {
        $invStmt = $conn->prepare("
            SELECT i.id, i.invoice_no, i.client_id, i.type, i.original_total_payable, i.cumulative_total_paid,
                   c.name, c.phone, c.email, c.gst_number
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE i.id = ?
        ");
        $invStmt->bind_param("i", $targetInvoiceId);
        $invStmt->execute();
        $invRes = $invStmt->get_result();
        if ($invRes->num_rows > 0) {
            $invRow = $invRes->fetch_assoc();
            $clientId = $invRow['client_id'];
            if (!$invoiceNo) $invoiceNo = $invRow['invoice_no'];
            if (empty($billToName)) $billToName = $invRow['name'];
            if (empty($phone)) $phone = $invRow['phone'];
            if (empty($email)) $email = $invRow['email'];
            if (empty($gstNumber)) $gstNumber = $invRow['gst_number'];
            $type = $invRow['type'];
            if ($originalTotalPayableInput === null || $originalTotalPayableInput <= 0) {
                $originalTotalPayableInput = floatval($invRow['original_total_payable']);
            }
        }
    } elseif ($invoiceNo) {
        // Load invoice and client info by invoice_no
        $invStmt = $conn->prepare("
            SELECT i.id, i.client_id, i.type, i.original_total_payable, i.cumulative_total_paid,
                   c.name, c.phone, c.email, c.gst_number
            FROM invoices i
            JOIN clients c ON i.client_id = c.id
            WHERE i.invoice_no = ?
        ");
        $invStmt->bind_param("s", $invoiceNo);
        $invStmt->execute();
        $invRes = $invStmt->get_result();
        if ($invRes->num_rows > 0) {
            $invRow = $invRes->fetch_assoc();
            $targetInvoiceId = $invRow['id'];
            $clientId = $invRow['client_id'];
            if (empty($billToName)) $billToName = $invRow['name'];
            if (empty($phone)) $phone = $invRow['phone'];
            if (empty($email)) $email = $invRow['email'];
            if (empty($gstNumber)) $gstNumber = $invRow['gst_number'];
            $type = $invRow['type'];
            if ($originalTotalPayableInput === null || $originalTotalPayableInput <= 0) {
                $originalTotalPayableInput = floatval($invRow['original_total_payable']);
            }
        }
    }

    if (empty($clientId)) {
        // Check if client exists by phone
        $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $client = $result->fetch_assoc();
            $clientId = $client['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO clients (name, phone, email, gst_number) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $billToName, $phone, $email, $gstNumber);
            $stmt->execute();
            $clientId = $conn->insert_id;
        }
    }
    
    // Check if we are in edit mode
    $isEditMode = false;
    $existingReceiptId = null;
    if ($editId) {
        $stmt = $conn->prepare("SELECT id, receipt_no, invoice_id FROM receipts WHERE id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $existingReceiptId = $row['id'];
            if (!$targetInvoiceId && !empty($row['invoice_id'])) $targetInvoiceId = $row['invoice_id'];
            $isEditMode = true;
            $receiptNo = $row['receipt_no'];
        }
    }
    
    if (!$isEditMode && $editReceiptNo) {
        $stmt = $conn->prepare("SELECT id, receipt_no, invoice_id FROM receipts WHERE receipt_no = ?");
        $stmt->bind_param("s", $editReceiptNo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $existingReceiptId = $row['id'];
            if (!$targetInvoiceId && !empty($row['invoice_id'])) $targetInvoiceId = $row['invoice_id'];
            $isEditMode = true;
            $receiptNo = $row['receipt_no'];
        }
    }

    // Calculate totals and date from items
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
    $receiptYear = function_exists('getFinancialYearYearFromDate') ? getFinancialYearYearFromDate($receiptDate) : date('Y', strtotime($receiptDate));

    if (!$isEditMode) {
        $receiptNo = generateReceiptNumber($conn, $clientId, $type, $continueFrom, $itemsJson, $receiptYear);
    }
    
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
    if ($targetInvoiceId) {
        if ($isEditMode) {
            $stmt = $conn->prepare("
                SELECT items 
                FROM receipts 
                WHERE (invoice_id = ? OR (invoice_no = ? AND invoice_no IS NOT NULL AND invoice_no != '')) AND id < ?
            ");
            $stmt->bind_param("isi", $targetInvoiceId, $invoiceNo, $existingReceiptId);
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
                WHERE invoice_id = ? OR (invoice_no = ? AND invoice_no IS NOT NULL AND invoice_no != '')
            ");
            $stmt->bind_param("is", $targetInvoiceId, $invoiceNo);
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
    
    // If cumulativeTotalPaid was explicitly passed from user input in edit form (and > 0), respect it, else compute prevSum + currentPaid
    if ($cumulativeTotalPaid > 0 && $isEditMode) {
        $totalCumulative = $cumulativeTotalPaid;
    } else {
        $totalCumulative = $prevSum + $currentPaid;
    }
    
    // Prevent overpayment beyond original invoice total
    if ($targetInvoiceId && $originalTotalPayable > 0 && $totalCumulative > ($originalTotalPayable + 0.05)) {
        $maxAllowed = max(0, $originalTotalPayable - $prevSum);
        echo json_encode([
            'success' => false,
            'error' => "Payment amount (₹" . number_format($currentPaid, 2) . ") exceeds remaining balance (₹" . number_format($maxAllowed, 2) . ") for Bill #" . $targetInvoiceId
        ]);
        exit;
    }

    // Status calculation
    $status = 'unpaid';
    $isFullyPaid = ($totalCumulative >= $originalTotalPayable - 0.01 && $originalTotalPayable > 0);
    if ($isFullyPaid) {
        $status = 'paid';
    } elseif ($totalCumulative > 0) {
        $status = 'partially_paid';
    }

    if ($isEditMode) {
        // Update existing receipt row
        $stmt = $conn->prepare("UPDATE receipts SET client_id = ?, invoice_id = ?, invoice_no = ?, type = ?, items = ?, original_total_payable = ?, cumulative_total_paid = ?, receipt_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("iisssddssi", $clientId, $targetInvoiceId, $invoiceNo, $type, $itemsJson, $originalTotalPayable, $totalCumulative, $receiptDate, $status, $existingReceiptId);
        $stmt->execute();
        log_action($conn, 'UPDATE', 'receipts', $existingReceiptId, "Updated receipt $receiptNo");

        // Cascade update to any subsequent installments in the same continuation group
        if ($targetInvoiceId) {
            $stmt = $conn->prepare("SELECT id, items, original_total_payable FROM receipts WHERE (invoice_id = ? OR invoice_no = ?) AND id > ? ORDER BY id ASC");
            $stmt->bind_param("isi", $targetInvoiceId, $invoiceNo, $existingReceiptId);
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
                if ($runningCumulative >= $subOrig - 0.01 && $subOrig > 0) {
                    $subStatus = 'paid';
                } elseif ($runningCumulative > 0) {
                    $subStatus = 'partially_paid';
                }
                
                $updateStmt = $conn->prepare("UPDATE receipts SET cumulative_total_paid = ?, status = ? WHERE id = ?");
                $updateStmt->bind_param("dsi", $runningCumulative, $subStatus, $subRec['id']);
                $updateStmt->execute();
            }
            $totalCumulative = $runningCumulative;
        }
    } else {
        // Insert new receipt row
        $stmt = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, invoice_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisssddss", $receiptNo, $clientId, $targetInvoiceId, $invoiceNo, $type, $itemsJson, $originalTotalPayable, $totalCumulative, $receiptDate, $status);
        $stmt->execute();
        $newReceiptId = $conn->insert_id;
        log_action($conn, 'CREATE', 'receipts', $newReceiptId, "Created receipt $receiptNo");
    }
    
    // Update invoice table if linked to an invoice
    $allocatedInvoiceNo = null;
    if ($targetInvoiceId) {
        $newInvoiceStatus = ($totalCumulative >= $originalTotalPayable - 0.01 && $originalTotalPayable > 0) ? 'paid' : 'partially_paid';
        $updInv = $conn->prepare("UPDATE invoices SET cumulative_total_paid = ?, status = ? WHERE id = ?");
        $updInv->bind_param("dsi", $totalCumulative, $newInvoiceStatus, $targetInvoiceId);
        $updInv->execute();

        // Check if 100% paid and needs invoice number allocated
        if ($newInvoiceStatus === 'paid') {
            $allocatedInvoiceNo = allocateInvoiceNumberIfNeeded($conn, $targetInvoiceId, $receiptDate);
            if ($allocatedInvoiceNo) {
                $invoiceNo = $allocatedInvoiceNo;
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    try {
        reconcileMerchantSettlements($conn);
    } catch (Throwable $tRec) {}
    
    echo json_encode([
        'success' => true,
        'receiptNo' => $receiptNo,
        'token' => encryptToken($receiptNo),
        'allocatedInvoiceNo' => $allocatedInvoiceNo,
        'invoiceNo' => $invoiceNo,
        'clientId' => $clientId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
