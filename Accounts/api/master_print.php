<?php
require_once 'config.php';
require_once 'invoice_utils.php';

$query = "SELECT i.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
          FROM invoices i 
          JOIN clients c ON i.client_id = c.id 
          ORDER BY i.id DESC 
          LIMIT 150";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("No invoices found to print.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Print - All Invoices</title>
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

        .invoice-page {
            background: white;
            width: 210mm;
            padding: 10mm;
            margin: 0 auto 30px auto;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
        }

        .invoice-inner { border: 1.5pt solid var(--border); height: 100%; border-radius: 4px; overflow: hidden; }
        .header-label { text-align: center; background: #f1f5f9; padding: 10px; font-weight: 800; text-transform: uppercase; border-bottom: 1.5pt solid var(--border); letter-spacing: 2px; }
        
        .top-info { display: flex; border-bottom: 1.5pt solid var(--border); }
        .logo-box { width: 40%; padding: 15px; text-align: center; border-right: 1.5pt solid var(--border); }
        .company-box { width: 60%; padding: 15px; font-size: 11px; }
        
        .bill-info { display: flex; border-bottom: 1.5pt solid var(--border); }
        .bill-to { width: 50%; padding: 10px; border-right: 1.5pt solid var(--border); min-height: 100px; }
        .inv-details { width: 50%; padding: 10px; }
        
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
            .invoice-page { margin: 0; box-shadow: none; page-break-after: always; }
        }
    </style>
</head>
<body>

    <div class="print-controls">
        <div style="font-weight: bold; color: #1e293b;">Master Invoice Batch (<?php echo $result->num_rows; ?> Records)</div>
        <button class="btn-print" onclick="window.print()">🖨️ Print All to PDF</button>
        <button class="btn-print" style="background: #64748b;" onclick="window.close()">Close</button>
    </div>

    <?php while ($inv = $result->fetch_assoc()): 
        $inv['address'] = '';
        $items = json_decode($inv['items'], true);
        $paidNow = floatval($items[0]['paidAmt'] ?? 0);
        $calcOriginalTotal = 0;
        if (is_array($items)) {
            foreach ($items as $item) {
                $calcOriginalTotal += floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
            }
        }
        $totalFee = ($calcOriginalTotal > 0) ? $calcOriginalTotal : floatval($inv['original_total_payable']);
        $totalPaid = floatval($inv['cumulative_total_paid']);
        $prevPaid = $totalPaid - $paidNow;
        $balance = $totalFee - $totalPaid;
    ?>
    <div class="invoice-page">
        <div class="invoice-inner">
            <div class="header-label"><?php echo $inv['type'] === 'gst' ? 'TAX INVOICE' : 'INVOICE'; ?></div>
            
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
                    <div style="font-size: 9px; color: #666; font-weight: bold;">BILL TO:</div>
                    <div style="font-size: 15px; font-weight: bold; margin: 5px 0;"><?php echo $inv['billToName']; ?></div>
                    <div style="font-size: 11px;">Phone: <?php echo $inv['phone']; ?></div>
                    <?php if (!empty($inv['email'])): ?><div style="font-size: 11px;">Email: <?php echo $inv['email']; ?></div><?php endif; ?>
                    <?php if (!empty($inv['address'])): ?><div style="font-size: 11px;">Address: <?php echo $inv['address']; ?></div><?php endif; ?>
                </div>
                <div class="inv-details">
                    <div style="font-size: 9px; color: #666; font-weight: bold;">INVOICE INFO:</div>
                    <div style="font-size: 12px; margin-top: 5px;"><strong>Invoice No:</strong> <?php echo $inv['invoice_no']; ?></div>
                    <div style="font-size: 12px;"><strong>Date:</strong> <?php echo date('d-M-Y', strtotime($inv['invoice_date'])); ?></div>
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
                    <?php foreach($items as $i => $item): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $i+1; ?></td>
                        <td style="font-weight: bold;"><?php echo $item['description']; ?></td>
                        <td style="text-align: right; font-weight: bold;">₹<?php echo number_format($item['amount'] ?? $item['totalInclTax'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="summary-section">
                <div class="words-box">
                    <div style="font-weight: bold; margin-bottom: 5px;">Amount in Words:</div>
                    <?php echo numberToWords($totalFee); ?> Rupees Only
                </div>
                <div class="totals-box">
                    <table class="totals-table">
                        <?php if ($balance <= 0.01): ?>
                        <tr><td>Total Amount:</td><td style="text-align: right;">₹<?php echo number_format($totalFee, 2); ?></td></tr>
                        <tr><td>Amount Paid:</td><td style="text-align: right;">₹<?php echo number_format($totalPaid, 2); ?></td></tr>
                        <tr class="grand-total"><td>Balance Due:</td><td style="text-align: right;">₹0.00</td></tr>
                        <?php else: ?>
                        <tr><td>Total Amount:</td><td style="text-align: right;">₹<?php echo number_format($totalFee, 2); ?></td></tr>
                        <tr style="font-weight: bold; border-top: 1pt solid #000;"><td>Current Payment Received:</td><td style="text-align: right;">₹<?php echo number_format($paidNow, 2); ?></td></tr>
                        <tr style="background: #f8fafc; font-weight: bold;"><td>Total Paid Till Date:</td><td style="text-align: right;">₹<?php echo number_format($totalPaid, 2); ?></td></tr>
                        <tr class="grand-total"><td>Balance Due:</td><td style="text-align: right;">₹<?php echo number_format($balance, 2); ?></td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="footer-section">
                <div class="bank-box">
                    <div style="font-weight: bold; margin-bottom: 5px; text-transform: uppercase;">BANK DETAILS:</div>
                    <table style="width: 100%; border: none !important; border-collapse: collapse; font-size: 10px; line-height: 1.5;">
                        <tr>
                            <td style="width: 100px; padding: 1px 0; border: none !important; font-weight: bold;">Bank Name</td>
                            <td style="width: 10px; padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important;">HDFC Bank</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">Account Name</td>
                            <td style="padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important;">TriShaKi Technologies Private Limited</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">Account Number</td>
                            <td style="padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important;">50200118025265</td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">IFSC Code</td>
                            <td style="padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important;">HDFC0010386</td>
                        </tr>
                        <tr>
                            <td colspan="3" style="padding: 3px 0 1px 0; border: none !important;"></td>
                        </tr>
                        <tr>
                            <td style="padding: 1px 0; border: none !important; font-weight: bold;">UPI ID</td>
                            <td style="padding: 1px 0; border: none !important;">:</td>
                            <td style="padding: 1px 0; border: none !important;">paytm.s2f1szd@pty</td>
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
