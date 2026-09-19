<?php
// Shared Utilities for Invoice Generation

function ensureInvoicesTableExists($conn) {
    try {
        $colCheck = $conn->query("SHOW COLUMNS FROM invoices LIKE 'invoice_date'");
        if ($colCheck && $row = $colCheck->fetch_assoc()) {
            if (strtoupper($row['Null'] ?? '') === 'NO') {
                $conn->query("ALTER TABLE invoices MODIFY COLUMN invoice_date DATE DEFAULT NULL");
            }
        }
    } catch (Throwable $e) {}
}

function generateInvoiceNumber($conn, $customerId, $type, $continueFrom, $items, $year = null) {
    // If continuing from existing invoice, return the clean existing invoice number
    if (!empty($continueFrom)) {
        if (preg_match('/^(.+?)(?:\/P\d+)?$/i', $continueFrom, $matches)) {
            return $matches[1];
        }
        return $continueFrom;
    }
    
    $year = $year ? strval($year) : (function_exists('getFinancialYearYearFromDate') ? getFinancialYearYearFromDate(date('Y-m-d')) : date('Y'));
    $prefix = "TSK-$year-";
    
    // Find the max number suffix in this specific year
    $stmt = $conn->prepare("
        SELECT invoice_no 
        FROM invoices 
        WHERE (invoice_no LIKE ? OR invoice_no LIKE ?)
    ");
    $patternPrimary = "TSK-$year-%";
    $patternLegacy = "TSK-GST-$year-%";
    $stmt->bind_param("ss", $patternPrimary, $patternLegacy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $maxNum = 0;
    while ($row = $result->fetch_assoc()) {
        $invNo = $row['invoice_no'];
        if (preg_match('/^TSK-(?:GST-)?' . $year . '-(\d+)/i', $invNo, $matches)) {
            $num = intval($matches[1]);
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    
    $nextNumber = $maxNum + 1;
    return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

function numberToWords($number) {
    if ($number === null || $number === '') return '';
    $number = (int)$number;
    $ones = array(0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen');
    $tens = array(2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety');
    
    if ($number == 0) return 'Zero';
    $words = '';
    if ($number >= 10000000) { $words .= numberToWords((int)($number / 10000000)) . ' Crore '; $number %= 10000000; }
    if ($number >= 100000) { $words .= numberToWords((int)($number / 100000)) . ' Lakh '; $number %= 100000; }
    if ($number >= 1000) { $words .= numberToWords((int)($number / 1000)) . ' Thousand '; $number %= 1000; }
    if ($number >= 100) { $words .= $ones[(int)($number / 100)] . ' Hundred '; $number %= 100; }
    if ($number >= 20) { $words .= $tens[(int)($number / 10)] . ' '; $number %= 10; }
    if ($number > 0) { $words .= $ones[$number] . ' '; }
    return trim($words);
}

function allocateInvoiceNumberIfNeeded($conn, $invoiceId, $optionalCustomDate = null) {
    if (!$invoiceId) return null;

    $stmt = $conn->prepare("SELECT id, invoice_no, invoice_date, client_id, type, items, original_total_payable, cumulative_total_paid FROM invoices WHERE id = ?");
    $stmt->bind_param("i", $invoiceId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) return null;
    $inv = $res->fetch_assoc();

    // If already has an invoice number, return it
    if (!empty($inv['invoice_no'])) {
        return $inv['invoice_no'];
    }

    $orig = floatval($inv['original_total_payable']);
    $paid = floatval($inv['cumulative_total_paid']);

    // Check if 100% paid
    if ($orig > 0 && $paid >= ($orig - 0.01)) {
        // Look up the latest payment receipt date for this invoice
        $stmtRec = $conn->prepare("SELECT MAX(receipt_date) as last_receipt_date FROM receipts WHERE invoice_id = ?");
        $stmtRec->bind_param("i", $invoiceId);
        $stmtRec->execute();
        $resRec = $stmtRec->get_result();
        $lastReceiptDate = null;
        if ($resRec && $recRow = $resRec->fetch_assoc()) {
            $lastReceiptDate = $recRow['last_receipt_date'];
        }

        // Determine effective date and FY year based on last payment receipt
        $effectiveDate = $lastReceiptDate ?: ($optionalCustomDate ?: ($inv['invoice_date'] ?: date('Y-m-d')));
        $effectiveYear = function_exists('getFinancialYearYearFromDate') ? getFinancialYearYearFromDate($effectiveDate) : date('Y', strtotime($effectiveDate));

        $allocatedNo = generateInvoiceNumber($conn, $inv['client_id'], $inv['type'], null, $inv['items'], $effectiveYear);
        
        $upd = $conn->prepare("UPDATE invoices SET invoice_no = ?, status = 'paid', invoice_date = ? WHERE id = ?");
        $upd->bind_param("ssi", $allocatedNo, $effectiveDate, $invoiceId);
        $upd->execute();

        // Update all linked receipts
        $updRec = $conn->prepare("UPDATE receipts SET invoice_no = ? WHERE invoice_id = ?");
        $updRec->bind_param("si", $allocatedNo, $invoiceId);
        $updRec->execute();

        // Update transactions ledger
        $updTx = $conn->prepare("UPDATE transactions SET description = CONCAT('Invoice Payment: ', ?, ' (Settled)') WHERE reference_table = 'invoices' AND reference_id = ?");
        $updTx->bind_param("si", $allocatedNo, $invoiceId);
        $updTx->execute();

        if (function_exists('log_action')) {
            log_action($conn, 'ALLOCATE_INVOICE_NO', 'invoices', $invoiceId, "Allocated invoice number $allocatedNo after 100% payment (Year: $effectiveYear based on receipt date $effectiveDate)");
        }

        return $allocatedNo;
    }

    return null;
}
?>
