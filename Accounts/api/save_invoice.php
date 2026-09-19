<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'invoice_utils.php';
require_once 'receipt_utils.php';
require_once 'settlement_helper.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Debug logging
error_log("save_invoice.php - Received data: " . json_encode($data));

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

ensureInvoicesTableExists($conn);
ensureReceiptsTableExists($conn);

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
$editId = !empty($data['id']) ? intval($data['id']) : (!empty($data['invoice_id']) ? intval($data['invoice_id']) : (!empty($data['invoiceId']) ? intval($data['invoiceId']) : null));

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if client exists
    $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Client exists, get ID without updating client details
        $client = $result->fetch_assoc();
        $clientId = $client['id'];
    } else {
        // Create new client if does not exist
        $stmt = $conn->prepare("INSERT INTO clients (name, phone, email, gst_number) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $billToName, $phone, $email, $gstNumber);
        $stmt->execute();
        $clientId = $conn->insert_id;
    }
    
    // Check if we are in edit mode
    $isEditMode = false;
    $existingInvoiceId = null;
    $invoiceNo = null;

    if ($editId) {
        $stmt = $conn->prepare("SELECT id, invoice_no, items, cumulative_total_paid, original_total_payable FROM invoices WHERE id = ?");
        $stmt->bind_param("i", $editId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $existingInv = $res->fetch_assoc();
            $existingInvoiceId = $existingInv['id'];
            $isEditMode = true;
            $invoiceNo = $existingInv['invoice_no'];
        }
    } elseif ($editInvoiceNo) {
        $stmt = $conn->prepare("SELECT id, invoice_no, items, cumulative_total_paid, original_total_payable FROM invoices WHERE invoice_no = ?");
        $stmt->bind_param("s", $editInvoiceNo);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $existingInv = $res->fetch_assoc();
            $existingInvoiceId = $existingInv['id'];
            $isEditMode = true;
            $invoiceNo = $existingInv['invoice_no'];
        }
    }

    if ($isEditMode) {
        // Fetch existing invoice row data to preserve items if not explicitly passed
        if ((empty($data['items']) || $data['items'] === '[]') && !empty($existingInv['items'])) {
            $parsedExisting = json_decode($existingInv['items'], true);
            if (is_array($parsedExisting) && count($parsedExisting) === 1 && $originalTotalPayableInput !== null && $originalTotalPayableInput > 0) {
                // Adjust single item's amount to match revised bill total
                $parsedExisting[0]['amount'] = $originalTotalPayableInput;
                $parsedExisting[0]['totalInclTax'] = $originalTotalPayableInput;
                $items = json_encode($parsedExisting);
            } else {
                $items = $existingInv['items'];
            }
        }
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

    // Extract base invoice prefix to identify continuation group if present
    $baseInvoice = null;
    if ($invoiceNo && preg_match('/^(TSK-(?:GST-)?\d{4}-\d{3})/i', $invoiceNo, $matches)) {
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
            if (preg_match('/^(TSK-(?:GST-)?\d{4}-\d{3})/i', $continueFrom, $matches)) {
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

    if ($isEditMode && $currentPaid == 0 && isset($existingInv['cumulative_total_paid']) && floatval($existingInv['cumulative_total_paid']) > 0) {
        $totalCumulative = floatval($existingInv['cumulative_total_paid']);
    } else {
        $totalCumulative = $prevSum + $currentPaid;
    }
    
    // Status calculation
    $status = 'unpaid';
    $isFullyPaid = ($totalCumulative >= $originalTotalPayable - 0.01 && $originalTotalPayable > 0);
    if ($isFullyPaid) {
        $status = 'paid';
    } elseif ($totalCumulative > 0) {
        $status = 'partially_paid';
    }

    $justAllocated = false;
    $dbInvoiceDate = ($isFullyPaid || !empty($invoiceNo)) ? $invoiceDate : null;

    if ($isEditMode) {
        // Update existing invoice row
        $stmt = $conn->prepare("UPDATE invoices SET client_id = ?, type = ?, items = ?, original_total_payable = ?, cumulative_total_paid = ?, invoice_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("issddssi", $clientId, $type, $items, $originalTotalPayable, $totalCumulative, $dbInvoiceDate, $status, $existingInvoiceId);
        $stmt->execute();

        // Check if invoice needs invoice number allocation (e.g., total reduced down to paid amount!)
        if (empty($invoiceNo) && $isFullyPaid) {
            $allocatedNo = allocateInvoiceNumberIfNeeded($conn, $existingInvoiceId, $invoiceDate);
            if ($allocatedNo) {
                $invoiceNo = $allocatedNo;
                $justAllocated = true;
            }
        }

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
                if ($runningCumulative >= $subOrig - 0.01 && $subOrig > 0) {
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
        // Only generate invoice number upfront IF bill is 100% paid!
        if ($isFullyPaid) {
            $invYear = function_exists('getFinancialYearYearFromDate') ? getFinancialYearYearFromDate($invoiceDate) : date('Y', strtotime($invoiceDate));
            $invoiceNo = generateInvoiceNumber($conn, $clientId, $type, $continueFrom, $items, $invYear);
        } else {
            $invoiceNo = null; // Unallocated pending 100% payment
            $dbInvoiceDate = null;
        }

        // Insert new invoice row
        $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, client_id, type, items, original_total_payable, cumulative_total_paid, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissddss", $invoiceNo, $clientId, $type, $items, $originalTotalPayable, $totalCumulative, $dbInvoiceDate, $status);
        $stmt->execute();
        $newInvoiceId = $conn->insert_id;
    }
    
    $targetInvId = $isEditMode ? $existingInvoiceId : $newInvoiceId;

    if ($isEditMode) {
        log_action($conn, 'EDIT', 'invoices', $targetInvId, "Edited bill/invoice #$targetInvId" . ($invoiceNo ? " ($invoiceNo)" : "") . " for $billToName");
    } else {
        log_action($conn, 'ADD', 'invoices', $targetInvId, "Created bill #$targetInvId" . ($invoiceNo ? " ($invoiceNo)" : " [Pending 100% Pay]") . " for $billToName");
    }
    
    // Sync with transactions ledger
    // Clear old transactions for this invoice instance
    $delStmt = $conn->prepare("DELETE FROM transactions WHERE reference_table = 'invoices' AND reference_id = ?");
    $delStmt->bind_param("i", $targetInvId);
    $delStmt->execute();
    
    // Insert new transaction if there is a payment
    if ($currentPaid > 0) {
        $tStmt = $conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('income', ?, ?, ?, 'invoices', ?)");
        $desc = "Invoice Payment: " . ($invoiceNo ? $invoiceNo : "Bill #$targetInvId") . " (" . $billToName . ")";
        $tStmt->bind_param("dsis", $currentPaid, $invoiceDate, $targetInvId, $desc);
        $tStmt->execute();
    }
    
    // Automatically generate/update Payment Receipt in receipts table
    ensureReceiptsTableExists($conn);
    $receiptNo = null;

    $recCheckStmt = $conn->prepare("
        SELECT id, receipt_no 
        FROM receipts 
        WHERE (invoice_id = ? AND invoice_id IS NOT NULL) 
           OR (invoice_no = ? AND invoice_no IS NOT NULL AND invoice_no != '') 
        ORDER BY id DESC LIMIT 1
    ");
    $recCheckStmt->bind_param("is", $targetInvId, $invoiceNo);
    $recCheckStmt->execute();
    $recCheckRes = $recCheckStmt->get_result();

    if ($recCheckRes->num_rows > 0) {
        $recRow = $recCheckRes->fetch_assoc();
        $existingRecId = $recRow['id'];
        $receiptNo = $recRow['receipt_no'];
        $updRec = $conn->prepare("UPDATE receipts SET client_id = ?, invoice_id = ?, invoice_no = ?, type = ?, items = ?, original_total_payable = ?, cumulative_total_paid = ?, receipt_date = ?, status = ? WHERE id = ?");
        $updRec->bind_param("iisssddssi", $clientId, $targetInvId, $invoiceNo, $type, $items, $originalTotalPayable, $totalCumulative, $invoiceDate, $status, $existingRecId);
        $updRec->execute();
    } elseif ($currentPaid > 0 || $totalCumulative > 0) {
        $recYear = function_exists('getFinancialYearYearFromDate') ? getFinancialYearYearFromDate($invoiceDate) : date('Y', strtotime($invoiceDate));
        $generatedReceiptNo = generateReceiptNumber($conn, $clientId, $type, null, $items, $recYear);
        $insRec = $conn->prepare("INSERT INTO receipts (receipt_no, client_id, invoice_id, invoice_no, type, items, original_total_payable, cumulative_total_paid, receipt_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insRec->bind_param("siisssddss", $generatedReceiptNo, $clientId, $targetInvId, $invoiceNo, $type, $items, $originalTotalPayable, $totalCumulative, $invoiceDate, $status);
        $insRec->execute();
        $newRecId = $conn->insert_id;
        $receiptNo = $generatedReceiptNo;
        log_action($conn, 'CREATE', 'receipts', $newRecId, "Auto-created receipt $generatedReceiptNo for bill #$targetInvId");
    }
    
    // Commit transaction
    $conn->commit();
    
    try {
        reconcileMerchantSettlements($conn);
    } catch (Throwable $tRec) {}
    
    echo json_encode([
        'success' => true,
        'invoiceId' => $targetInvId,
        'invoiceNo' => $invoiceNo,
        'justAllocated' => $justAllocated,
        'isFullyPaid' => $isFullyPaid,
        'receiptNo' => $receiptNo,
        'clientId' => $clientId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
