<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'invoice_utils.php';
require_once 'receipt_utils.php';

ensureInvoicesTableExists($conn);
ensureReceiptsTableExists($conn);

try {
    $fy = getFinancialYearDates();
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
            c.name as billToName,
            c.phone,
            c.email,
            c.gst_number as gstNumber,
            (
                SELECT r.receipt_no 
                FROM receipts r 
                WHERE r.invoice_id = i.id 
                   OR (r.invoice_no = i.invoice_no AND i.invoice_no IS NOT NULL AND i.invoice_no != '') 
                ORDER BY r.id DESC LIMIT 1
            ) as last_receipt_no,
            (
                SELECT r.items 
                FROM receipts r 
                WHERE r.invoice_id = i.id 
                   OR (r.invoice_no = i.invoice_no AND i.invoice_no IS NOT NULL AND i.invoice_no != '') 
                ORDER BY r.id DESC LIMIT 1
            ) as last_receipt_items
        FROM invoices i
        JOIN clients c ON i.client_id = c.id
        WHERE (i.invoice_date >= ? AND i.invoice_date <= ?)
           OR (i.invoice_date IS NULL AND DATE(i.created_at) >= ? AND DATE(i.created_at) <= ?)
        ORDER BY COALESCE(i.invoice_date, DATE(i.created_at)) DESC, i.id DESC
    ");
    
    $stmt->bind_param("ssss", $fy['start_date'], $fy['end_date'], $fy['start_date'], $fy['end_date']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $invoices = [];
    while ($row = $result->fetch_assoc()) {
        $invId = intval($row['id']);
        $invNo = $row['invoice_no'];
        $lastRecNo = $row['last_receipt_no'] ?? null;
        $lastPaidAmt = 0;
        
        if (!empty($row['last_receipt_items'])) {
            $rItems = json_decode($row['last_receipt_items'], true);
            if (is_array($rItems)) {
                foreach ($rItems as $ritm) {
                    $lastPaidAmt += floatval($ritm['paidAmt'] ?? $ritm['amount'] ?? 0);
                }
            }
        }

        // Calculate dynamic total paid from all active receipts for this invoice
        $stmtSum = $conn->prepare("
            SELECT items 
            FROM receipts 
            WHERE invoice_id = ? 
               OR (invoice_no = ? AND invoice_no IS NOT NULL AND invoice_no != '')
        ");
        $stmtSum->bind_param("is", $invId, $invNo);
        $stmtSum->execute();
        $resSum = $stmtSum->get_result();
        $cumPaid = 0;
        while ($recRow = $resSum->fetch_assoc()) {
            $rItems = json_decode($recRow['items'] ?? '[]', true);
            if (is_array($rItems)) {
                foreach ($rItems as $ritm) {
                    $cumPaid += floatval($ritm['paidAmt'] ?? $ritm['amount'] ?? 0);
                }
            }
        }

        $origPayable = floatval($row['original_total_payable']);

        // Check if fully paid and needs allocation
        if ($origPayable > 0 && $cumPaid >= ($origPayable - 0.01) && empty($invNo)) {
            $allocatedNo = allocateInvoiceNumberIfNeeded($conn, $invId);
            if ($allocatedNo) {
                $invNo = $allocatedNo;
            }
        }

        // Update database if cumulative_total_paid in invoices table is out of sync
        if (abs(floatval($row['cumulative_total_paid']) - $cumPaid) > 0.001) {
            $syncStatus = ($cumPaid >= $origPayable - 0.01 && $origPayable > 0) ? 'paid' : ($cumPaid > 0 ? 'partially_paid' : 'unpaid');
            $upd = $conn->prepare("UPDATE invoices SET cumulative_total_paid = ?, status = ? WHERE id = ?");
            $upd->bind_param("dsi", $cumPaid, $syncStatus, $invId);
            $upd->execute();
        }
        
        $pendingAmt = max(0, $origPayable - $cumPaid);
        $status = ($pendingAmt <= 0.01 && $origPayable > 0) ? 'Paid' : 'Pending';

        $invoices[] = [
            'id' => $invId,
            'invoiceNo' => $invNo,
            'invoice_no' => $invNo,
            'type' => $row['type'],
            'items' => $row['items'],
            'originalTotalPayable' => $origPayable,
            'cumulativeTotalPaid' => $cumPaid,
            'pendingAmount' => $pendingAmt,
            'lastReceiptNo' => $lastRecNo ? $lastRecNo : '-',
            'lastAmountPaid' => $lastPaidAmt,
            'status' => $status,
            'billToName' => $row['billToName'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'gstNumber' => $row['gstNumber'],
            'address' => '',
            'date' => !empty($invNo) ? $row['invoice_date'] : null,
            'invoice_date' => !empty($invNo) ? $row['invoice_date'] : null,
            'generatedAt' => $row['created_at']
        ];
    }
    
    echo json_encode($invoices);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>