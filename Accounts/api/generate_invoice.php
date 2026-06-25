<?php
// Get invoice parameters (check if already set by bulk_print_invoices.php first)
$type = isset($type) ? $type : (isset($_GET['type']) ? $_GET['type'] : 'non-gst');
$billToName = isset($billToName) ? $billToName : (isset($_GET['billToName']) ? htmlspecialchars($_GET['billToName']) : 'N/A');
$phone = isset($phone) ? $phone : (isset($_GET['phone']) ? htmlspecialchars($_GET['phone']) : 'N/A');
$email = isset($email) ? $email : (isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '');
$gstNumber = isset($gstNumber) ? $gstNumber : (isset($_GET['gstNumber']) ? htmlspecialchars($_GET['gstNumber']) : '');
$address = isset($address) ? $address : (isset($_GET['address']) ? htmlspecialchars($_GET['address']) : '');
$date = isset($date) ? $date : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
$invoiceNo = isset($invoiceNo) ? $invoiceNo : (isset($_GET['invoiceNo']) ? htmlspecialchars($_GET['invoiceNo']) : 'TSK-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT));
$originalTotalPayable = isset($originalTotalPayable) ? $originalTotalPayable : (isset($_GET['originalTotalPayable']) ? floatval($_GET['originalTotalPayable']) : null);
$cumulativeTotalPaid = isset($cumulativeTotalPaid) ? $cumulativeTotalPaid : (isset($_GET['cumulativeTotalPaid']) ? floatval($_GET['cumulativeTotalPaid']) : null);

// Parse items from JSON
if (!isset($items) || empty($items)) {
    if (isset($_GET['items'])) {
        $items = json_decode($_GET['items'], true);
    }
}

// If no items, use default
if (empty($items)) {
    $items = [
        [
            'description' => 'Course Fee Payment',
            'amount' => 5000.00, // Fixed 5000 as requested
            'totalInclTax' => 5000.00,
            'paidAmt' => 5000.00,
            'paymentMode' => 'Cash',
            'date' => date('Y-m-d')
        ]
    ];
}

$isContinuation = (strpos($invoiceNo, '/P') !== false);
// Calculate total amount from items if not provided or zero, or if it is NOT a continuation
if (!$isContinuation) {
    $calcTotal = 0;
    foreach ($items as $item) {
        if ($type === 'gst') {
            $calcTotal += floatval($item['totalInclTax'] ?? $item['amount'] ?? 0);
        } else {
            $calcTotal += floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
        }
    }
    $originalTotalPayable = $calcTotal > 0 ? $calcTotal : 5000.00;
} else {
    if (!$originalTotalPayable || $originalTotalPayable <= 0) {
        $originalTotalPayable = 5000.00;
    }
}

// Robust Date handling: Use payment date from first item if main date is missing or invalid
if (empty($date) || $date === 'undefined' || strtotime($date) === false) {
    if (!empty($items) && isset($items[0]['date']) && strtotime($items[0]['date']) !== false) {
        $date = $items[0]['date'];
    } else {
        $date = date('Y-m-d');
    }
}

// Calculate totals
$totalAmount = 0;
$totalPaidCurr = 0; // Current payment total
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
        $totalInclTax = floatval($item['totalInclTax'] ?? $item['amount'] ?? $item['paidAmt'] ?? 0);
        $paidAmt = floatval($item['paidAmt'] ?? $totalInclTax);
        
        $grandTotal += $totalInclTax;
        $totalPaidCurr += $paidAmt;
        $totalGst += floatval($item['gst'] ?? 0);
        $totalAmount += floatval($item['charges'] ?? ($totalInclTax / 1.18));
    }
    $sgst = $totalGst / 2; // 9%
    $cgst = $totalGst / 2; // 9%
    
    // Part payment logic
    if ($cumulativeTotalPaid !== null) {
        $cumulativeGrandTotal = $originalTotalPayable;
        $cumulativePaid = floatval($cumulativeTotalPaid); // use DB value directly
        $dueAmount = $cumulativeGrandTotal - $cumulativePaid;
        $hasDue = $dueAmount > 0;
    } else {
        $cumulativeGrandTotal = $isContinuation ? $originalTotalPayable : $grandTotal;
        $cumulativePaid = $totalPaidCurr;
        $dueAmount = $cumulativeGrandTotal - $totalPaidCurr;
        $hasDue = $dueAmount > 0;
    }
} else {
    // For non-GST invoices
    foreach ($items as $item) {
        // Fix for "undefined array key amount"
        $itemAmt = floatval($item['amount'] ?? $item['totalInclTax'] ?? $item['paidAmt'] ?? 0);
        $paidAmt = floatval($item['paidAmt'] ?? $itemAmt);
        
        $totalAmount += $itemAmt;
        $totalPaidCurr += $paidAmt;
    }
    $grandTotal = $totalAmount;
    
    // Part payment logic
    if ($cumulativeTotalPaid !== null) {
        $cumulativeGrandTotal = $originalTotalPayable;
        $cumulativePaid = floatval($cumulativeTotalPaid); // use DB value directly
        $dueAmount = $cumulativeGrandTotal - $cumulativePaid;
        $hasDue = $dueAmount > 0;
    } else {
        $cumulativeGrandTotal = $isContinuation ? $originalTotalPayable : $grandTotal;
        $cumulativePaid = $totalPaidCurr;
        $dueAmount = $cumulativeGrandTotal - $totalPaidCurr;
        $hasDue = $dueAmount > 0;
    }
}
?>
<?php if (!isset($bulkPrintMode)): ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?php echo $invoiceNo; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<?php endif; ?>
    <?php
    // Function to convert number to words
    if (!function_exists('numberToWords')) {
    function numberToWords($number) {
        $number = (int)$number;
        
        $ones = array(
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        );
        
        $tens = array(
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        );
        
        if ($number == 0) return 'Zero';
        
        $words = '';
        
        if ($number >= 10000000) { // Crores
            $crores = (int)($number / 10000000);
            $words .= numberToWords($crores) . ' Crore ';
            $number %= 10000000;
        }
        
        if ($number >= 100000) { // Lakhs
            $lakhs = (int)($number / 100000);
            $words .= numberToWords($lakhs) . ' Lakh ';
            $number %= 100000;
        }
        
        if ($number >= 1000) { // Thousands
            $thousands = (int)($number / 1000);
            $words .= numberToWords($thousands) . ' Thousand ';
            $number %= 1000;
        }
        
        if ($number >= 100) { // Hundreds
            $hundreds = (int)($number / 100);
            $words .= $ones[$hundreds] . ' Hundred ';
            $number %= 100;
        }
        
        if ($number >= 20) {
            $words .= $tens[(int)($number / 10)] . ' ';
            $number %= 10;
        }
        
        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }
        
        return trim($words);
    }
    }
    
    // Calculate total charges for words (Charges Inc of Tax = total paid amount for the current invoice)
    $totalChargesForWords = $type === 'gst' ? $grandTotal : $grandTotal;
    $amountInWords = numberToWords($totalChargesForWords) . ' Rupees Only';
    ?>
<?php if (!isset($bulkPrintMode)): ?>
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
        }
        
        .amount-in-words {
            width: 60%;
            padding: 10px;
            border-right: 1px solid var(--primary-black);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .amount-in-words-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 5px;
            color: var(--text-grey);
        }
        
        .amount-in-words-text {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .summary-table {
            width: 40%;
            border-collapse: collapse;
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
        }

        .signature-box {
            width: 35%;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            text-align: center;
            border-left: 1px solid var(--primary-black);
        }
        .sig-wrap {
            height: 110px;
            overflow: hidden;
            width: 100%;
            margin-bottom: 2mm;
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
            width: 330px;
            height: auto;
            margin-top: -90px;
            margin-left: -40px;
            transform: rotate(5deg);
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
<?php endif; ?>

    <div class="invoice-container">
        <div class="invoice-inner">
            <div class="tax-invoice-label"><?php echo $type === 'gst' ? 'TAX INVOICE' : 'INVOICE'; ?></div>

            <div class="header-section">
                <div class="header-left">
                    <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="TriShaKi Logo">
                </div>
                <div class="header-right">
                    <div class="company-name">TRISHAKI TECHNOLOGIES PRIVATE LIMITED</div>
                    <div class="company-address">
                        F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank,<br>
                        Tilakwadi, Belagavi, Karnataka - 590006<br>
                        <strong>Phone:</strong> (+91) 9980681304 | (+91) 9148710506<br>
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
                        <?php if ($address): ?>
                        <div><strong>Address:</strong> <?php echo $address; ?></div>
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
                        <div><strong>Invoice Date:</strong> <?php 
                            $tempTs = strtotime($date);
                            // Avoid 30-Nov--0001 or Jan 1970 on invalid data
                            if (!$tempTs || $tempTs < 0) $tempTs = time();
                            echo date('d-M-Y', $tempTs); 
                        ?></div>
                        <?php if (!empty($items)): ?>
                        <div><strong>Payment Mode:</strong> <?php echo htmlspecialchars($items[0]['paymentMode']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="table-section">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th width="10%" style="white-space: nowrap;">Sl No</th>
                            <th width="<?php echo $type === 'gst' ? ($hasDescColumn ? '66%' : '76%') : '76%'; ?>">Description</th>
                            <?php if ($type === 'gst'): ?>
                            <th width="<?php echo $hasDescColumn ? '15%' : '14%'; ?>" style="text-align: right; white-space: nowrap;">Charges (Exc. of Tax)</th>
                            <?php if ($hasDescColumn): ?>
                            <th width="9%" style="text-align: center;">Desc.%</th>
                            <?php endif; ?>
                            <?php else: ?>
                            <th width="14%" style="text-align: right;">Charges</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['description']); ?></strong></td>
                            <?php if ($type === 'gst'): ?>
                            <?php 
                                // Show FULL item charge (incl tax) then find Exc. of Tax
                                $rowTotalItem = floatval($item['totalInclTax'] ?? $item['amount'] ?? 5000);
                                $chargesExcTax = $rowTotalItem / 1.18;
                            ?>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($chargesExcTax, 2); ?></td>
                            <?php if ($hasDescColumn): ?>
                            <td style="text-align: center;"><?php echo isset($item['hasDesc']) && $item['hasDesc'] ? '✓' : '-'; ?></td>
                            <?php endif; ?>
                            <?php else: ?>
                            <?php 
                                // Show FULL item charge
                                $rowTotalItem = floatval($item['amount'] ?? $item['totalInclTax'] ?? 5000);
                            ?>
                            <td style="text-align: right; font-weight: 700;">₹<?php echo number_format($rowTotalItem, 2); ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if ($type === 'gst'): ?>
                        <!-- SGST Row -->
                        <tr>
                            <td style="border-right: 1px solid var(--primary-black);"></td>
                            <td style="text-align: right; padding-right: 10px; border-right: none;"><strong>State Tax (SGST) 9%</strong></td>
                            <td style="text-align: right; font-weight: 700; border-right: 1px solid var(--primary-black);">₹<?php echo number_format($sgst, 2); ?></td>
                            <?php if ($hasDescColumn): ?>
                            <td style="border-right: none;"></td>
                            <?php endif; ?>
                        </tr>
                        <!-- CGST Row -->
                        <tr>
                            <td style="border-right: 1px solid var(--primary-black);"></td>
                            <td style="text-align: right; padding-right: 10px; border-right: none;"><strong>Central Tax (CGST) 9%</strong></td>
                            <td style="text-align: right; font-weight: 700; border-right: 1px solid var(--primary-black);">₹<?php echo number_format($cgst, 2); ?></td>
                            <?php if ($hasDescColumn): ?>
                            <td style="border-right: none;"></td>
                            <?php endif; ?>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="summary-section">
                <div class="amount-in-words">
                    <div class="amount-in-words-label">Amount Chargeable (In Words):</div>
                    <div class="amount-in-words-text"><?php echo $amountInWords; ?></div>
                </div>
                <table class="summary-table">
                    <?php if ($dueAmount <= 0.01): ?>
                    <tr style="background: #f2f2f2;">
                        <td class="summary-label" style="font-size: 12px; font-weight: 800;">Total Amount:</td>
                        <td class="summary-value" style="font-size: 13px; font-weight: 800;">₹<?php echo number_format($cumulativeGrandTotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Amount Paid:</td>
                        <td class="summary-value" style="font-weight: 700; color: #059669;">₹<?php echo number_format($cumulativePaid, 2); ?></td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd;">
                        <td class="summary-label">Balance Due:</td>
                        <td class="summary-value" style="font-weight: 700;">₹0.00</td>
                    </tr>
                    <?php else: ?>
                    <tr style="background: #f2f2f2;">
                        <td class="summary-label" style="font-size: 12px; font-weight: 800;">Total Amount:</td>
                        <td class="summary-value" style="font-size: 13px; font-weight: 800;">₹<?php echo number_format($cumulativeGrandTotal, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Current Payment Received:</td>
                        <td class="summary-value" style="font-weight: 700; color: #059669;">₹<?php echo number_format($totalPaidCurr, 2); ?></td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd;">
                        <td class="summary-label">Total Paid Till Date:</td>
                        <td class="summary-value">₹<?php echo number_format($cumulativePaid, 2); ?></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Balance Due:</td>
                        <td class="summary-value" style="color: #dc2626; font-weight: 700;">₹<?php echo number_format($dueAmount, 2); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <?php if ($hasDue): ?>
            <?php endif; ?>

            <div class="footer-boxes" style="display: flex; border-top: 1px solid #000;">
                <!-- Column 1: Bank Details -->
                <div class="bank-details" style="flex: 2.5; padding: 10px 12px;">
                    <div class="section-title" style="font-size: 10px; font-weight: bold; text-transform: uppercase; margin-bottom: 6px; color: #555;">Bank Details:</div>
                    <div class="footer-content">
                        <table style="width: 100%; border: none !important; border-collapse: collapse; font-size: 11px; line-height: 1.6;">
                            <tr>
                                <td style="width: 110px; padding: 2px 0; border: none !important; font-weight: bold; color: var(--text-dark);">Bank Name</td>
                                <td style="width: 15px; padding: 2px 0; border: none !important; color: var(--text-dark);">:</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">HDFC Bank</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0; border: none !important; font-weight: bold; color: var(--text-dark);">Account Name</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">:</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">TriShaKi Technologies Private Limited</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0; border: none !important; font-weight: bold; color: var(--text-dark);">Account Number</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">:</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">50200118025265</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0; border: none !important; font-weight: bold; color: var(--text-dark);">IFSC Code</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">:</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">HDFC0010386</td>
                            </tr>
                            <tr>
                                <td style="padding: 2px 0; border: none !important; font-weight: bold; color: var(--text-dark);">Branch</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">:</td>
                                <td style="padding: 2px 0; border: none !important; color: var(--text-dark);">Bhagyanagar RPD Cross Belagavi Main</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <!-- Column 2: QR Code -->
                <div style="flex: 1; padding: 10px 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                    <?php if (file_exists('../assets/image.png')): ?>
                    <div style="font-size: 10px; font-weight: bold; color: #333; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">Scan to Pay</div>
                    <img src="../assets/image.png" alt="UPI QR Code" style="width: 110px; height: 110px; border: 1px solid #ddd; border-radius: 4px; display: block;">
                    <?php else: ?>
                    <div style="color: #999; font-size: 10px;">QR Not Available</div>
                    <?php endif; ?>
                </div>
                <!-- Column 3: Signature -->
                <div class="signature-box" style="flex: 1.2; border-left: 1px solid #000; padding: 10px 12px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; text-align: center;">
                    <?php if (file_exists('../assets/ningaraj_sign_blue.png')): ?>
                    <div class="sig-wrap" style="height: 110px; overflow: hidden; margin-bottom: 6px; width: 100%;">
                        <img src="../assets/ningaraj_sign_blue.png" alt="Signature" class="seal-img">
                    </div>
                    <?php else: ?>
                    <div style="height: 90px; display: flex; align-items: center; justify-content: center; margin-bottom: 6px;">
                        <span style="font-size: 48px;">🔒</span>
                    </div>
                    <?php endif; ?>
                    <div style="padding-top: 5px; width: 100%;">
                        <div class="sig-line" style="font-size: 10px; font-weight: bold; text-transform: uppercase; color: #222;">Authorized Signatory</div>
                        <div style="font-size: 9px; color: #555; margin-top: 2px; font-weight: 700; text-transform: uppercase;">TriShaKi Technologies Pvt. Ltd.</div>
                    </div>
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
<?php if (!isset($bulkPrintMode)): ?>
</body>
</html>
<?php endif; ?>
