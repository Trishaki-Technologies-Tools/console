<?php
// Get invoice parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'non-gst';
$billToName = isset($_GET['billToName']) ? htmlspecialchars($_GET['billToName']) : 'N/A';
$phone = isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : 'N/A';
$email = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';
$gstNumber = isset($_GET['gstNumber']) ? htmlspecialchars($_GET['gstNumber']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$invoiceNo = isset($_GET['invoiceNo']) ? htmlspecialchars($_GET['invoiceNo']) : 'TSK-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
$originalTotalPayable = isset($_GET['originalTotalPayable']) ? floatval($_GET['originalTotalPayable']) : null;
$cumulativeTotalPaid = isset($_GET['cumulativeTotalPaid']) ? floatval($_GET['cumulativeTotalPaid']) : null;

// Parse items from JSON
$items = [];
if (isset($_GET['items'])) {
    $items = json_decode($_GET['items'], true);
}

// If no items, use default
if (empty($items)) {
    $items = [
        [
            'description' => 'Course Fee Payment',
            'amount' => 10000.00,
            'paymentMode' => 'Cash'
        ]
    ];
}

// Calculate totals
$totalAmount = 0;
$totalPaid = 0;
$totalGst = 0;
$grandTotal = 0;
$hasDue = false;
$hasDescColumn = false;

// Check if any item has Desc.% checked (for GST invoices)
if ($type === 'gst') {
    foreach ($items as $item) {
        if (isset($item['hasDesc']) && $item['hasDesc']) {
            $hasDescColumn = true;
            break;
        }
    }
}

if ($type === 'gst') {
    // For GST invoices
    foreach ($items as $item) {
        $totalInclTax = floatval($item['totalInclTax']);
        $paidAmt = floatval($item['paidAmt']);
        $grandTotal += $totalInclTax;
        $totalPaid += $paidAmt;
        $totalGst += floatval($item['gst']);
        $totalAmount += floatval($item['charges']);
    }
    $sgst = $totalGst / 2; // 9%
    $cgst = $totalGst / 2; // 9%
    
    // Use original total payable if this is a part payment
    if ($originalTotalPayable !== null && $cumulativeTotalPaid !== null) {
        $cumulativeGrandTotal = $originalTotalPayable;
        // cumulativeTotalPaid already includes all previous payments, just add current payment
        $cumulativePaid = floatval($cumulativeTotalPaid) + $totalPaid;
        $dueAmount = $cumulativeGrandTotal - $cumulativePaid;
        $hasDue = $dueAmount > 0;
    } else {
        $cumulativeGrandTotal = $grandTotal;
        $cumulativePaid = $totalPaid;
        $dueAmount = $grandTotal - $totalPaid;
        $hasDue = $dueAmount > 0;
    }
} else {
    // For non-GST invoices
    foreach ($items as $item) {
        $totalAmt = floatval($item['amount']);
        $paidAmt = floatval($item['paidAmt'] ?? $item['amount']);
        $totalAmount += $totalAmt;
        $totalPaid += $paidAmt;
    }
    $grandTotal = $totalAmount;
    
    // Use original total payable if this is a part payment
    if ($originalTotalPayable !== null && $cumulativeTotalPaid !== null) {
        $cumulativeGrandTotal = $originalTotalPayable;
        // cumulativeTotalPaid already includes all previous payments, just add current payment
        $cumulativePaid = floatval($cumulativeTotalPaid) + $totalPaid;
        $dueAmount = $cumulativeGrandTotal - $cumulativePaid;
        $hasDue = $dueAmount > 0;
    } else {
        $cumulativeGrandTotal = $grandTotal;
        $cumulativePaid = $totalPaid;
        $dueAmount = $grandTotal - $totalPaid;
        $hasDue = $dueAmount > 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $invoice_no; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-black: #000000;
            --text-dark: #1a1a1a;
            --text-grey: #4a4a4a;
            --border-color: #000000;
            --bg-light: #f9f9f9;
            --bg-grey: #f2f2f2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #d8d8d8;
            color: var(--text-dark);
            line-height: 1.4;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-container {
            width: 210mm;
            height: 297mm;
            margin: 20px auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            position: relative;
            padding: 8mm;
        }

        .invoice-inner {
            border: 1px solid var(--primary-black);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .tax-invoice-label {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3mm 0;
            border-bottom: 1px solid var(--primary-black);
            background: var(--bg-grey);
            letter-spacing: 2px;
        }

        .header-section {
            display: flex;
            border-bottom: 1px solid var(--primary-black);
        }

        .header-left {
            width: 40%;
            padding: 5mm;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-left img {
            width: 100%;
            max-width: 180px;
            height: auto;
        }

        .header-right {
            width: 60%;
            padding: 5mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .company-name {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 1mm;
            color: var(--primary-black);
        }

        .company-address {
            font-size: 10px;
            line-height: 1.5;
            color: var(--text-grey);
        }

        .billing-section {
            display: flex;
            border-bottom: 1px solid var(--primary-black);
        }

        .bill-to-box {
            width: 50%;
            padding: 4mm;
            border-right: 1px solid var(--primary-black);
        }

        .invoice-info-box {
            width: 50%;
            padding: 4mm;
        }

        .section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 2mm;
            color: var(--text-grey);
            border-bottom: 1px solid #ddd;
            padding-bottom: 1mm;
        }

        .billing-content {
            font-size: 11px;
            line-height: 1.6;
        }

        .billing-content strong {
            font-weight: 700;
            color: var(--primary-black);
        }

        .billing-content .name {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 1mm;
        }

        .table-section {
            flex-grow: 1;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table th {
            background: var(--bg-grey);
            border-bottom: 1px solid var(--primary-black);
            border-right: 1px solid var(--primary-black);
            padding: 8px 10px;
            font-size: 10px;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
        }

        .invoice-table th:last-child {
            border-right: none;
        }

        .invoice-table td {
            padding: 10px;
            border-bottom: 1px solid var(--primary-black);
            border-right: 1px solid var(--primary-black);
            font-size: 11px;
            vertical-align: top;
        }

        .invoice-table td:last-child {
            border-right: none;
        }

        .summary-section {
            border-top: 1px solid var(--primary-black);
            display: flex;
            justify-content: flex-end;
        }

        .summary-table {
            width: 40%;
            border-collapse: collapse;
            border-left: 1px solid var(--primary-black);
        }

        .summary-table td {
            padding: 6px 10px;
            font-size: 11px;
            border-bottom: 1px solid #eee;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 600;
            text-align: left;
        }

        .summary-value {
            text-align: right;
            font-weight: 700;
        }

        .total-row {
            background: var(--bg-grey);
            font-weight: 800;
        }

        .footer-boxes {
            display: flex;
            border-top: 1px solid var(--primary-black);
        }

        .bank-details {
            width: 65%;
            padding: 4mm;
            border-right: 1px solid var(--primary-black);
        }

        .signature-box {
            width: 35%;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .footer-content {
            font-size: 10px;
            line-height: 1.5;
            color: var(--text-dark);
        }

        .footer-content strong {
            font-weight: 700;
        }

        .seal-img {
            width: 150px;
            height: auto;
            margin-bottom: 2mm;
            transform: rotate(-15deg);
        }

        .sig-line {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .terms-section {
            border-top: 1px solid var(--primary-black);
            padding: 3mm 5mm;
            background: var(--bg-light);
        }

        .terms-text {
            font-size: 8.5px;
            color: var(--text-grey);
            line-height: 1.4;
            font-style: italic;
        }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #000;
            color: #fff;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            z-index: 9999;
            font-weight: 700;
            border-radius: 6px;
        }

        @media print {
            body {
                background: white;
            }

            .invoice-container {
                margin: 0;
                box-shadow: none;
                width: 100%;
                height: auto;
            }

            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">🖨️ PRINT INVOICE</button>

    <div class="invoice-container">
        <div class="invoice-inner">
            <div class="tax-invoice-label"><?php echo $type === 'gst' ? 'TAX INVOICE' : 'INVOICE'; ?></div>

            <div class="header-section">
                <div class="header-left">
                    <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="TriShaKi Logo">
                </div>
                <div class="header-right">
                    <div class="company-name">TRISHAKI TECHNOLOGIES PVT LTD</div>
                    <div class="company-address">
                        F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank,<br>
                        Tilakwadi, Belagavi, Karnataka - 590006<br>
                        <strong>Phone:</strong> (+91) 9980681304<br>
                        <strong>CIN:</strong> U62010KA2025PTC213183<br>
                        <strong>Email:</strong> info@trishaki.com | <strong>Web:</strong> www.trishaki.com
                    </div>
                </div>
            </div>

            <div class="billing-section">
                <div class="bill-to-box">
                    <div class="section-title">Bill To:</div>
                    <div class="billing-content">
                        <div class="name"><?php echo $billToName; ?></div>
                        <div><strong>Phone:</strong> <?php echo $phone; ?></div>
                        <?php if ($email): ?>
                        <div><strong>Email:</strong> <?php echo $email; ?></div>
                        <?php endif; ?>
                        <?php if ($type === 'gst' && $gstNumber): ?>
                        <div><strong>GSTIN:</strong> <?php echo $gstNumber; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="invoice-info-box">
                    <div class="section-title">Invoice Information:</div>
                    <div class="billing-content">
                        <div><strong>Invoice No:</strong> <?php echo $invoiceNo; ?></div>
                        <div><strong>Invoice Date:</strong> <?php echo date('d-M-Y', strtotime($date)); ?></div>
                    </div>
                </div>
            </div>

            <div class="table-section">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th width="6%">#</th>
                            <th width="<?php echo $type === 'gst' ? ($hasDescColumn ? '16%' : '18%') : '35%'; ?>">Description</th>
                            <?php if ($type === 'gst'): ?>
                            <th width="10%">Mode of Payment</th>
                            <th width="8%" style="text-align: center;">GST Rate</th>
                            <th width="10%">Date</th>
                            <th width="<?php echo $hasDescColumn ? '12%' : '14%'; ?>" style="text-align: right;">Charges (incl tax)</th>
                            <th width="<?php echo $hasDescColumn ? '12%' : '14%'; ?>" style="text-align: right;">Charges</th>
                            <?php if ($hasDescColumn): ?>
                            <th width="8%" style="text-align: center;">Desc.%</th>
                            <?php endif; ?>
                            <th width="<?php echo $hasDescColumn ? '12%' : '14%'; ?>" style="text-align: right;">Amount</th>
                            <?php else: ?>
                            <th width="20%">Payment Mode</th>
                            <th width="15%">Date</th>
                            <th width="20%" style="text-align: right;">Amount</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['description']); ?></strong></td>
                            <?php if ($type === 'gst'): ?>
                            <td><?php echo htmlspecialchars($item['paymentMode']); ?></td>
                            <td style="text-align: center; font-weight: 600;">18%</td>
                            <td><?php echo date('d-M-Y', strtotime($item['date'])); ?></td>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($item['paidAmt'], 2); ?></td>
                            <td style="text-align: right;">₹<?php echo number_format($item['charges'], 2); ?></td>
                            <?php if ($hasDescColumn): ?>
                            <td style="text-align: center;"><?php echo isset($item['hasDesc']) && $item['hasDesc'] ? '✓' : '-'; ?></td>
                            <?php endif; ?>
                            <td style="text-align: right;">₹<?php echo number_format($item['charges'], 2); ?></td>
                            <?php else: ?>
                            <td><?php echo htmlspecialchars($item['paymentMode']); ?></td>
                            <td><?php echo date('d-M-Y', strtotime($item['date'] ?? date('Y-m-d'))); ?></td>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($item['paidAmt'] ?? $item['amount'], 2); ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if ($type === 'gst'): ?>
                        <!-- SGST Row -->
                        <tr>
                            <td></td>
                            <td colspan="<?php echo $hasDescColumn ? '7' : '6'; ?>" style="text-align: center;"><strong>State Tax (SGST) 9%</strong></td>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($sgst, 2); ?></td>
                        </tr>
                        <!-- CGST Row -->
                        <tr>
                            <td></td>
                            <td colspan="<?php echo $hasDescColumn ? '7' : '6'; ?>" style="text-align: center;"><strong>Central Tax (CGST) 9%</strong></td>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($cgst, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="summary-section">
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Total Payable:</td>
                        <td class="summary-value">₹<?php echo number_format($cumulativeGrandTotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Total Paid (Till Date):</td>
                        <td class="summary-value">₹<?php echo number_format($cumulativePaid, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Balance Due:</td>
                        <td class="summary-value">₹<?php echo number_format($dueAmount, 2); ?></td>
                    </tr>
                </table>
            </div>

            <?php if ($hasDue): ?>
            <?php endif; ?>

            <div class="footer-boxes">
                <div class="bank-details">
                    <div class="section-title">Payment Information:</div>
                    <div class="footer-content">
                        <strong>Bank Transfer:</strong><br>
                        Account Name: TrishKI Technologies Pvt Ltd<br>
                        Account Number: 12345678901 | Bank: ICICI Bank<br>
                        IFSC Code: ICIC0001234 | Branch: Belagavi Main<br><br>
                        <strong>UPI Payment:</strong><br>
                        UPI ID: TRISHAKI@ICICI | Name: TrishKI Technologies
                    </div>
                </div>
                <div class="signature-box">
                    <?php if (file_exists('../assets/sign.jpg')): ?>
                    <img src="../assets/sign.jpg" alt="Signature" class="seal-img">
                    <?php else: ?>
                    <div style="height: 100px; display: flex; align-items: center; justify-content: center;">
                        <span style="font-size: 48px;">🔒</span>
                    </div>
                    <?php endif; ?>
                    <div class="sig-line">Authorized Signatory</div>
                </div>
            </div>

            <div class="terms-section">
                <div class="terms-text">
                    * This is a computer-generated invoice and does not require a physical signature.<br>
                    * Terms: Fees once paid are non-refundable. Please keep this invoice for your records.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
