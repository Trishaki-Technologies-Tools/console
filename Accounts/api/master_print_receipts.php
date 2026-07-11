<?php
require_once 'config.php';
require_once 'receipt_utils.php';

$query = "SELECT r.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
          FROM receipts r 
          JOIN clients c ON r.client_id = c.id 
          ORDER BY r.id DESC 
          LIMIT 150";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("No receipts found to print.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Print - All Receipts</title>
    <style>
        :root { --primary: #0077c8; --border: #000; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', sans-serif; background: #525659; padding: 40px 0; }
        
        .print-controls {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            background: white;
            padding: 15px 30px;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .btn-print {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .receipt-page {
            background: white;
            width: 210mm;
            padding: 10mm;
            margin: 0 auto 30px auto;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .receipt-inner { border: 1.5pt solid var(--border); height: 100%; border-radius: 4px; overflow: hidden; }
        .header-label { text-align: center; background: #f1f5f9; padding: 10px; font-weight: 800; text-transform: uppercase; border-bottom: 1.5pt solid var(--border); letter-spacing: 2px; }
        
        .top-info { display: flex; border-bottom: 1.5pt solid var(--border); }
        .logo-box { width: 40%; padding: 15px; text-align: center; border-right: 1.5pt solid var(--border); }
        .company-box { width: 60%; padding: 15px; font-size: 11px; }
        
        .bill-info { display: flex; border-bottom: 1.5pt solid var(--border); }
        .bill-to { width: 50%; padding: 10px; border-right: 1.5pt solid var(--border); min-height: 100px; }
        .rec-details { width: 50%; padding: 10px; }
        
        .items-table { width: 100%; border-collapse: collapse; min-height: 400px; }
        .items-table th { background: #f1f5f9; border-bottom: 1.5pt solid var(--border); border-right: 1pt solid var(--border); padding: 8px; font-size: 10px; text-align: left; }
        .items-table td { border-right: 1pt solid var(--border); border-bottom: 1pt solid #eee; padding: 10px; font-size: 12px; }
        .items-table td:last-child { border-right: none; }

        .summary-section { display: flex; border-top: 1.5pt solid var(--border); }
        .words-box { width: 60%; padding: 10px; border-right: 1.5pt solid var(--border); font-size: 10px; font-style: italic; }
        .totals-box { width: 40%; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 6px 10px; border-bottom: 1pt solid #eee; font-size: 11px; }
        .grand-total { background: #000; color: white; font-weight: bold; }
        
        .footer-section { display: flex; border-top: 1.5pt solid var(--border); }
        .bank-box { width: 60%; padding: 10px; border-right: 1.5pt solid var(--border); font-size: 10px; }
        .sig-box { width: 40%; padding: 10px; text-align: center; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; }
        .sig-wrap { height: 60px; overflow: hidden; width: 100%; margin-bottom: 5px; text-align: center; }
        .sig-img { width: 330px; height: auto; margin-top: -130px; margin-left: -40px; transform: rotate(5deg); }

        @media print {
            body { background: white; padding: 0; }
            .print-controls { display: none; }
            .receipt-page { margin: 0; box-shadow: none; page-break-after: always; }
        }
    </style>
</head>
<body>

    <div class="print-controls">
        <div style="font-weight: bold; color: #1e293b;">Master Receipt Batch (<?php echo $result->num_rows; ?> Records)</div>
        <button class="btn-print" onclick="window.print()">🖨️ Print All to PDF</button>
        <button class="btn-print" style="background: #64748b;" onclick="window.close()">Close</button>
    </div>

    <?php while ($rec = $result->fetch_assoc()): 
        $rec['address'] = '';
        $items = json_decode($rec['items'], true);
        
        // Calculate how much was paid on this receipt from receipt items
        $paidNow = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $paidNow += floatval($item['paidAmt'] ?? $item['amount'] ?? $item['totalInclTax'] ?? 0);
            }
        }
        
        $invoiceNo = $rec['invoice_no'] ?? '';
        $dbOriginalTotal = floatval($rec['original_total_payable']);
        $dbCumulativePaid = floatval($rec['cumulative_total_paid']);

        if (!empty($invoiceNo) && isset($conn)) {
            $invStmt = $conn->prepare("SELECT original_total_payable, cumulative_total_paid, items FROM invoices WHERE invoice_no = ?");
            if ($invStmt) {
                $invStmt->bind_param("s", $invoiceNo);
                $invStmt->execute();
                $invRes = $invStmt->get_result();
                if ($invRow = $invRes->fetch_assoc()) {
                    $dbOriginalTotal = floatval($invRow['original_total_payable']);
                    $dbCumulativePaid = floatval($invRow['cumulative_total_paid']);
                    // Override receipt items with parent invoice items to match description
                    if (!empty($invRow['items'])) {
                        $invoiceItems = json_decode($invRow['items'], true);
                        if (is_array($invoiceItems) && !empty($invoiceItems)) {
                            $items = $invoiceItems;
                        }
                    }
                }
            }
        }

        $calcOriginalTotal = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $calcOriginalTotal += floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
            }
        }

        $totalFee = ($dbOriginalTotal > 0) ? $dbOriginalTotal : (($calcOriginalTotal > 0) ? $calcOriginalTotal : 5000.00);
        $totalPaid = $dbCumulativePaid;
        $prevPaid = $totalPaid - $paidNow;
        $balance = $totalFee - $totalPaid;
    ?>
    <div class="receipt-page">
        <div class="receipt-inner">
            <div class="header-label"><?php echo $rec['type'] === 'gst' ? 'TAX PAYMENT RECEIPT' : 'PAYMENT RECEIPT'; ?></div>
            
            <div class="top-info">
                <div class="logo-box">
                    <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" style="width: 150px;" onerror="this.src='https://via.placeholder.com/150x60?text=TriShaKi'">
                </div>
                <div class="company-box">
                    <h2 style="color: var(--primary); font-size: 16px;">TRISHAKI TECHNOLOGIES PRIVATE LIMITED</h2>
                    <p>F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank, Tilakwadi, Belagavi, Karnataka - 590006</p>
                    <p><strong>Phone:</strong> 9980681304 | <strong>CIN:</strong> U62010KA2025PTC213183</p>
                </div>
            </div>

            <div class="bill-info">
                <div class="bill-to">
                    <div style="font-size: 9px; color: #666; font-weight: bold;">RECEIVED FROM:</div>
                    <div style="font-size: 15px; font-weight: bold; margin: 5px 0;"><?php echo $rec['billToName']; ?></div>
                    <div style="font-size: 11px;">Phone: <?php echo $rec['phone']; ?></div>
                    <?php if (!empty($rec['email'])): ?><div style="font-size: 11px;">Email: <?php echo $rec['email']; ?></div><?php endif; ?>
                    <?php if (!empty($rec['address'])): ?><div style="font-size: 11px;">Address: <?php echo $rec['address']; ?></div><?php endif; ?>
                </div>
                <div class="rec-details">
                    <div style="font-size: 9px; color: #666; font-weight: bold;">RECEIPT INFO:</div>
                    <div style="font-size: 12px; margin-top: 5px;"><strong>Receipt No:</strong> <?php echo $rec['receipt_no']; ?></div>
                    <div style="font-size: 12px;"><strong>Date:</strong> <?php echo date('d-M-Y', strtotime($rec['receipt_date'])); ?></div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center; white-space: nowrap;">Sl No</th>
                        <th>Description</th>
                        <th style="width: 120px; text-align: right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($items as $i => $item): 
                        $itemAmt = floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
                        $itemPaidAmt = ($totalFee > 0) ? (($itemAmt / $totalFee) * $paidNow) : $itemAmt;
                    ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $i+1; ?></td>
                        <td style="font-weight: bold;"><?php echo $item['description']; ?></td>
                        <td style="text-align: right; font-weight: bold;">₹<?php echo number_format($itemPaidAmt, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary-section">
                <div class="words-box">
                    <div style="font-weight: bold; margin-bottom: 5px;">Amount in Words:</div>
                    <?php echo numberToWords($paidNow); ?> Rupees Only
                </div>
                <div class="totals-box">
                    <table class="totals-table">
                        <tr class="grand-total"><td>Amount Received:</td><td style="text-align: right;">₹<?php echo number_format($paidNow, 2); ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="footer-section">
                <div class="bank-box">
                    <div style="font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">OUTSTANDING SUMMARY:</div>
                    <table style="width: 100%; border: none !important; border-collapse: collapse; font-size: 10px; line-height: 1.5;">
                        <tr>
                            <td style="width: 100px; padding: 1px 0; border: none !important; font-weight: bold;">Total Amount</td>
                            <td style="width: 10px; padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">₹<?php echo number_format($totalFee, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">Paid Till Date</td>
                            <td style="padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important; color: #10b981; font-weight: bold;">₹<?php echo number_format($totalPaid, 2); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">Balance Due</td>
                            <td style="padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important; color: <?php echo ($balance > 0.01) ? '#ef4444' : '#000'; ?>; font-weight: bold;">₹<?php echo number_format($balance, 2); ?></td>
                        </tr>
                    </table>
                </div>
                <div class="sig-box">
                    <div class="sig-wrap">
                        <img src="../assets/ningaraj_sign_blue.png" class="sig-img" onerror="this.style.display='none'">
                    </div>
                    <div class="sig-line">Authorized Signatory</div>
                    <div style="font-size: 8px; color: #555; margin-top: 2px; font-weight: 700; text-transform: uppercase;">TriShaKi Technologies Private Limited</div>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>

</body>
</html>
