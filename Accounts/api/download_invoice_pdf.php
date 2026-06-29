<?php
/**
 * Master PDF Generator - 100% Visual Fidelity with Screenshot 2
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'invoice_utils.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$invoiceNo = $_GET['invoiceNo'] ?? '';
if (!$invoiceNo) die("Invoice number required.");

// 1. Fetch Data
$stmt = $conn->prepare("
    SELECT i.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
    FROM invoices i JOIN clients c ON i.client_id = c.id 
    WHERE i.invoice_no = ?
");
$stmt->bind_param("s", $invoiceNo);
$stmt->execute();
$inv = $stmt->get_result()->fetch_assoc();
if (!$inv) die("Invoice not found.");
$inv['address'] = '';

$items = json_decode($inv['items'], true);
$type = $inv['type'];

// 2. Pre-Calculate Values
$calcOriginalTotal = 0;
$itemsHtml = "";
if (is_array($items)) {
    foreach ($items as $i => $item) {
        $itemAmt = floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
        $calcOriginalTotal += $itemAmt;
        $itemsHtml .= "
        <tr>
            <td class='text-center' style='white-space: nowrap;'>".($i+1)."</td>
            <td class='bold'>".htmlspecialchars($item['description'])."</td>
            <td class='text-right bold'>₹".number_format($itemAmt, 2)."</td>
        </tr>";
    }
}
$originalTotal = ($calcOriginalTotal > 0) ? $calcOriginalTotal : floatval($inv['original_total_payable']);
$cumulativePaid = floatval($inv['cumulative_total_paid']);
$paidThisInstallment = floatval($items[0]['paidAmt'] ?? 0);
$previouslyPaid = $cumulativePaid - $paidThisInstallment;
$balanceDue = $originalTotal - $cumulativePaid;
$amountInWords = numberToWords($originalTotal) . ' Rupees Only';
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

// 5. Build HTML (Matching Screenshot 2 Exactly)
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
            <tr><td colspan='2' class='header-label text-center'>".($type === 'gst' ? 'TAX INVOICE' : 'INVOICE')."</td></tr>
            
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
                    <div class='section-title uppercase'>BILL TO:</div>
                    <div class='bill-name'>{$inv['billToName']}</div>
                    <div style='font-size:10px;'>Phone: {$inv['phone']}</div>
                    ".(!empty($inv['email']) ? "<div style='font-size:10px;'>Email: {$inv['email']}</div>" : "")."
                    ".(!empty($inv['address']) ? "<div style='font-size:10px;'>Address: {$inv['address']}</div>" : "")."
                </td>
                <td class='cell'>
                    <div class='section-title uppercase'>INVOICE INFORMATION:</div>
                    <table style='width:100%; font-size: 11px; border-collapse: collapse;'>
                        <tr><td class='bold'>Invoice No:</td><td class='text-right'>{$inv['invoice_no']}</td></tr>
                        <tr><td class='bold'>Invoice Date:</td><td class='text-right'>".date('d-M-Y', strtotime($inv['invoice_date']))."</td></tr>
                        <tr><td class='bold'>Payment Mode:</td><td class='text-right'>".($items[0]['paymentMode'] ?? 'Online')."</td></tr>
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
                                <th style='width: 20%; text-align: right;'>CHARGES</th>
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
                        ".($balanceDue <= 0.01 ? "
                        <tr><td class='bold'>Total Amount:</td><td class='text-right bold'>₹".number_format($originalTotal, 2)."</td></tr>
                        <tr><td class='bold'>Amount Paid:</td><td class='text-right bold'>₹".number_format($cumulativePaid, 2)."</td></tr>
                        <tr style='background: #fdf2f2;'><td class='bold'>Balance Due:</td><td class='text-right bold'>₹0.00</td></tr>
                        " : "
                        <tr><td class='bold'>Total Amount:</td><td class='text-right bold'>₹".number_format($originalTotal, 2)."</td></tr>
                        <tr><td class='green bold'>Current Payment Received:</td><td class='text-right green bold'>₹".number_format($paidThisInstallment, 2)."</td></tr>
                        <tr><td class='bold'>Total Paid Till Date:</td><td class='text-right bold'>₹".number_format($cumulativePaid, 2)."</td></tr>
                        <tr style='background: #fdf2f2;'><td class='red'>Balance Due:</td><td class='text-right red'>₹".number_format($balanceDue, 2)."</td></tr>
                        ")."
                    </table>
                </td>
            </tr>
            
            <!-- Footer: 3-column: Bank Details | QR Code | Signature -->
            <tr>
                <td colspan='2' style='padding: 0; border: 0.8pt solid #000;'>
                    <table style='width: 100%; border-collapse: collapse; table-layout: fixed;'>
                        <tr>
                            <!-- Column 1: Bank Details -->
                            <td style='width: 55%; border: none; padding: 8px 10px; vertical-align: top; font-size: 8.5px; line-height: 1.5; color: #444;'>
                                <div class='section-title uppercase' style='margin-bottom: 5px;'>BANK DETAILS:</div>
                                <table style='width: 100%; border: none !important; border-collapse: collapse; font-size: 8.5px; line-height: 1.5;'>
                                    <tr>
                                        <td style='width: 90px; padding: 1px 0; border: none !important; font-weight: bold;'>Bank Name</td>
                                        <td style='width: 8px; padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important;'>HDFC Bank</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 1px 0; border: none !important; font-weight: bold;'>Account Name</td>
                                        <td style='padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important;'>TriShaKi Technologies Private Limited</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 1px 0; border: none !important; font-weight: bold;'>Account Number</td>
                                        <td style='padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important;'>50200118025265</td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 1px 0; border: none !important; font-weight: bold;'>IFSC Code</td>
                                        <td style='padding: 1px 0; border: none !important;'>:</td>
                                        <td style='padding: 1px 0; border: none !important;'>HDFC0010386</td>
                                    </tr>
                                </table>
                            </td>
                            <!-- Column 2: QR Code -->
                            <td style='width: 20%; border: none; padding: 8px 6px; vertical-align: middle; text-align: center; font-size: 8px;'>
                                ".(!empty($qrCode) ? "
                                <div style='font-size: 7.5px; font-weight: bold; color: #333; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;'>Scan to Pay</div>
                                <img src='$qrCode' style='width: 80px; height: 80px; border: 0.8pt solid #ccc; display: block; margin: 0 auto;'>
                                " : "<div style='color:#999;font-size:7px;'>QR Not Available</div>")."
                            </td>
                            <!-- Column 3: Signature -->
                            <td style='width: 25%; border: none; border-left: 1pt solid #000; padding: 8px 10px; vertical-align: bottom; text-align: center;'>
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
                    * This is a computer-generated invoice and does not require a physical signature.<br>
                    * Terms: Fees once paid are non-refundable. Please keep this invoice for your records.
                </td>
            </tr>
        </table>
    </div>
</body>
</html>";

// 6. Render Fast
$dompdf = new Dompdf($options);
$dompdf->setPaper('A4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$filename = "Invoice_" . preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoiceNo) . ".pdf";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $dompdf->output();
exit;
