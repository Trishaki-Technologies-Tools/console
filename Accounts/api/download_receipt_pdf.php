<?php
/**
 * Master PDF Generator for Receipts
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'receipt_utils.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$receiptNo = $_GET['receiptNo'] ?? '';
if (!$receiptNo) die("Receipt number required.");

// 1. Fetch Data
$stmt = $conn->prepare("
    SELECT r.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
    FROM receipts r JOIN clients c ON r.client_id = c.id 
    WHERE r.receipt_no = ?
");
$stmt->bind_param("s", $receiptNo);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
if (!$rec) die("Receipt not found.");
$rec['address'] = '';

$items = json_decode($rec['items'], true);
$type = $rec['type'];

// Calculate how much was paid on this receipt from receipt items
$paidThisInstallment = 0;
$receiptPaymentMode = 'Online';
if (is_array($items)) {
    foreach ($items as $item) {
        $paidThisInstallment += floatval($item['paidAmt'] ?? $item['amount'] ?? $item['totalInclTax'] ?? 0);
        if (!empty($item['paymentMode'])) {
            $receiptPaymentMode = $item['paymentMode'];
        }
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

// 2. Pre-Calculate Values
$calcOriginalTotal = 0;
if (is_array($items)) {
    foreach ($items as $item) {
        $calcOriginalTotal += floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
    }
}

$originalTotal = ($dbOriginalTotal > 0) ? $dbOriginalTotal : (($calcOriginalTotal > 0) ? $calcOriginalTotal : 5000.00);
$cumulativePaid = $dbCumulativePaid;
$previouslyPaid = $cumulativePaid - $paidThisInstallment;
$balanceDue = $originalTotal - $cumulativePaid;

$itemsHtml = "";
if (is_array($items)) {
    foreach ($items as $i => $item) {
        $itemAmt = floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
        $itemPaidAmt = ($originalTotal > 0) ? (($itemAmt / $originalTotal) * $paidThisInstallment) : $itemAmt;
        $itemsHtml .= "
        <tr>
            <td class='text-center' style='white-space: nowrap;'>".($i+1)."</td>
            <td class='bold'>".htmlspecialchars($item['description'])."</td>
            <td class='text-right bold'>₹".number_format($itemPaidAmt, 2)."</td>
        </tr>";
    }
}
$amountInWords = numberToWords($paidThisInstallment) . ' Rupees Only';
$rowHeight = count($items) === 1 ? '320px' : 'auto';

// 3. Assets
function getBase64($path) {
    if (file_exists($path)) {
        $data = @file_get_contents($path);
        return 'data:image/'.pathinfo($path, PATHINFO_EXTENSION).';base64,'.base64_encode($data);
    }
    return '';
}
$logo = getBase64('../assets/TRISHAKI LOGO TRANSPERANT BG.png');
$sign = getBase64('../assets/ningaraj_sign_blue.png');
$qrCode = getBase64('../assets/image.png');

// 4. Dompdf Options for Speed
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans'); // Better support for Rupee symbol

// 5. Build HTML
$html = "
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'DejaVu Sans', sans-serif; margin: 0; padding: 0; font-size: 10px; color: #1a1a1a; background: #fff; line-height: 1.4; }
        .container { width: 210mm; padding: 8mm; box-sizing: border-box; }
        .master-table { width: 100%; border: 0.8pt solid #000; border-collapse: collapse; table-layout: fixed; }
        .cell { border: 0.8pt solid #000; padding: 8px 10px; vertical-align: top; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header-label { font-size: 13px; font-weight: bold; padding: 6px; letter-spacing: 2px; border-bottom: 0.8pt solid #000; }
        .company-name { font-size: 15px; font-weight: bold; color: #2563eb; margin-bottom: 2px; }
        .address-text { font-size: 9px; color: #333; line-height: 1.3; }
        
        .section-title { font-size: 8px; color: #555; margin-bottom: 3px; font-weight: bold; }
        .bill-name { font-size: 13px; font-weight: bold; margin-bottom: 2px; }

        .items-table { width: 100%; border-collapse: collapse; margin-top: -0.8pt; }
        .items-table th { background: #f9fafb; border: 0.8pt solid #000; padding: 6px; font-size: 9px; text-align: left; }
        .items-table td { border: 0.8pt solid #000; padding: 10px; font-size: 11px; height: {$rowHeight}; vertical-align: top; }
        
        .summary-row { display: table; width: 100%; border-top: 0.8pt solid #000; }
        .totals-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .totals-table td { padding: 5px 10px; border-bottom: 0.5pt solid #eee; }
        
        .green { color: #10b981; }
        .red { color: #ef4444; font-weight: bold; }

        .footer-info { font-size: 8.5px; line-height: 1.4; color: #444; }
        .sig-text { font-size: 8px; font-weight: bold; margin-top: 5px; }
        .sig-cell { vertical-align: bottom; padding-bottom: 15px; width: 35%; text-align: center; }
        .sig-wrap { height: 60px; overflow: hidden; margin-bottom: 5px; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <table class='master-table'>
            <!-- Header Label -->
            <tr><td colspan='2' class='header-label text-center'>".($type === 'gst' ? 'TAX PAYMENT RECEIPT' : 'PAYMENT RECEIPT')."</td></tr>
            
            <!-- Logo & Company -->
            <tr>
                <td class='cell text-center' style='width: 35%;'><img src='$logo' style='width: 130px;'></td>
                <td class='cell' style='width: 65%;'>
                    <div class='company-name'>TRISHAKI TECHNOLOGIES PRIVATE LIMITED</div>
                    <div class='address-text'>
                        F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank,<br>
                        Tilakwadi, Belagavi, Karnataka - 590006 | Phone: (+91) 9980681304<br>
                        CIN: U62010KA2025PTC213183 | Email: info@trishaki.com | Web: www.trishaki.com
                    </div>
                </td>
            </tr>
            
            <!-- Billing Info -->
            <tr>
                <td class='cell'>
                    <div class='section-title uppercase'>RECEIVED FROM:</div>
                    <div class='bill-name'>{$rec['billToName']}</div>
                    <div style='font-size:10px;'>Phone: {$rec['phone']}</div>
                    ".(!empty($rec['email']) ? "<div style='font-size:10px;'>Email: {$rec['email']}</div>" : "")."
                    ".(!empty($rec['address']) ? "<div style='font-size:10px;'>Address: {$rec['address']}</div>" : "")."
                </td>
                <td class='cell'>
                    <div class='section-title uppercase'>RECEIPT INFORMATION:</div>
                    <table style='width:100%; font-size: 11px; border-collapse: collapse;'>
                        <tr><td class='bold'>Receipt No:</td><td class='text-right'>{$rec['receipt_no']}</td></tr>
                        ".(!empty($rec['invoice_no']) ? "<tr><td class='bold'>Invoice No:</td><td class='text-right'>{$rec['invoice_no']}</td></tr>" : "")."
                        <tr><td class='bold'>Receipt Date:</td><td class='text-right'>".date('d-M-Y', strtotime($rec['receipt_date']))."</td></tr>
                        <tr><td class='bold'>Payment Mode:</td><td class='text-right'>{$receiptPaymentMode}</td></tr>
                    </table>
                </td>
            </tr>
            
            <!-- Items -->
            <tr>
                <td colspan='2' style='padding: 0;'>
                    <table class='items-table'>
                        <thead>
                            <tr>
                                <th style='width: 10%; text-align: center; white-space: nowrap;'>Sl No</th>
                                <th style='width: 70%;'>DESCRIPTION</th>
                                <th style='width: 20%; text-align: right;'>AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                        </tbody>
                    </table>
                </td>
            </tr>
            
            <!-- Totals -->
            <tr>
                <td class='cell' style='font-style: italic;'>
                    <div class='section-title uppercase'>AMOUNT CHARGEABLE (IN WORDS):</div>
                    <div style='padding-top: 5px; font-size: 10.5px;'>$amountInWords</div>
                </td>
                <td class='cell' style='padding: 0;'>
                    <table class='totals-table'>
                        <tr><td class='bold'>Amount Received:</td><td class='text-right bold green' style='font-size: 11px;'>₹".number_format($paidThisInstallment, 2)."</td></tr>
                    </table>
                </td>
            </tr>
            
            <!-- Footer: 2-column: Outstanding Summary | Signature -->
            <tr>
                <td colspan='2' style='padding: 0; border: 0.8pt solid #000;'>
                    <table style='width: 100%; border-collapse: collapse; table-layout: fixed;'>
                        <tr>
                            <!-- Column 1: Outstanding Summary -->
                            <td style='width: 70%; border: none; padding: 8px 10px; vertical-align: top; font-size: 8.5px; line-height: 1.5; color: #444;'>
                                <div class='section-title uppercase' style='margin-bottom: 5px;'>OUTSTANDING SUMMARY:</div>
                                <table style='width: 100%; border: none !important; border-collapse: collapse; font-size: 8.5px; line-height: 1.5;'>
                                    <tr>
                                        <td style='width: 110px; padding: 1px 0; border: none !important; font-weight: bold;'>Total Amount</td>
                                        <td style='width: 8px; padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important; font-weight: bold;'>₹".number_format($originalTotal, 2)."</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 1px 0; border: none !important; font-weight: bold;'>Paid Till Date</td>
                                        <td style='padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important; color: #10b981; font-weight: bold;'>₹".number_format($cumulativePaid, 2)."</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 1px 0; border: none !important; font-weight: bold;'>Balance Due</td>
                                        <td style='padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important; color: ".($balanceDue > 0.01 ? '#ef4444' : '#444')."; font-weight: bold;'>₹".number_format($balanceDue, 2)."</td>
                                    </tr>
                                </table>
                            </td>
                            <!-- Column 2: Signature -->
                            <td style='width: 30%; border: none; border-left: 1pt solid #000; padding: 8px 10px; vertical-align: bottom; text-align: center;'>
                                <div style='height: 90px; overflow: hidden; margin-bottom: 4px; display: block; text-align: center;'>
                                    <img src='$sign' style='width: 320px; margin-top: -80px; margin-left: -30px; transform: rotate(5deg);'>
                                </div>
                                <div style='font-size: 8px; font-weight: bold; text-transform: uppercase; color: #222;'>Authorized Signatory</div>
                                <div style='font-size: 7.5px; color: #444; margin-top: 2px; font-weight: bold;'>TriShaKi Technologies Pvt. Ltd.</div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <!-- Terms -->
            <tr>
                <td colspan='2' class='cell' style='font-size: 8.5px; font-style: italic; border-top: none; padding: 5px 10px; color: #555;'>
                    * This is a computer-generated receipt and does not require a physical signature.<br>
                    * Terms: Fees once paid are non-refundable. Please keep this receipt for your records.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>";

// 6. Render
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$filename = "Receipt_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $receiptNo) . ".pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $dompdf->output();
exit;
?>
