<?php
header("Location: master_print.php");
exit;
// ob_start(); // Commented out to see errors directly

require_once 'config.php';
require_once 'invoice_utils.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validation
if (!extension_loaded('gd')) {
    die("Error: PHP GD extension is required.");
}
if (!class_exists('ZipArchive')) {
    die("Error: ZipArchive extension is not enabled.");
}

// Fetch invoices
$query = "SELECT i.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
          FROM invoices i 
          JOIN clients c ON i.client_id = c.id 
          ORDER BY i.id DESC 
          LIMIT 150";
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("No invoices found.");
}

// Setup Dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

// Pre-load assets as Base64 helper
function safeImgToBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = @file_get_contents($path);
        if ($data) return 'data:image/'.$type.';base64,'.base64_encode($data);
    }
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=='; // Tiny transparent pixel
}

$logoBase64 = safeImgToBase64('../assets/TRISHAKI LOGO TRANSPERANT BG.png');
$signBase64 = safeImgToBase64('../assets/ningaraj_sign_blue.png');

// Robust Shared CSS
$sharedStyle = '
    @page { size: A4; margin: 0; }
    body { font-family: Helvetica, sans-serif; margin: 0; padding: 0; font-size: 11px; color: #333; background: #fff; }
    .invoice-container { width: 210mm; height: 297mm; padding: 8mm; margin: 0; box-shadow: none; }
    .invoice-inner { border: 1px solid #000; height: 100%; width: 100%; }
    .tax-invoice-label { text-align: center; font-size: 14px; font-weight: 800; text-transform: uppercase; padding: 3mm 0; border-bottom: 1px solid #000; background: #f2f2f2; letter-spacing: 2px; }
    .header-section { width: 100%; border-bottom: 1px solid #000; overflow: hidden; clear: both; }
    .header-left { float: left; width: 35%; padding: 5mm; text-align: center; }
    .header-right { float: left; width: 55%; padding: 5mm; }
    .company-name { font-size: 16px; font-weight: 800; margin-bottom: 1mm; color: #000; }
    .billing-section { width: 100%; border-bottom: 1px solid #000; overflow: hidden; clear: both; }
    .bill-to-box { float: left; width: 45%; padding: 4mm; border-right: 1px solid #000; min-height: 80px; }
    .invoice-info-box { float: left; width: 45%; padding: 4mm; }
    .invoice-table { width: 100%; border-collapse: collapse; }
    .invoice-table th { background: #f2f2f2; border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 8px 10px; font-size: 10px; }
    .invoice-table td { padding: 8px 10px; border-bottom: 1px solid #000; border-right: 1px solid #000; vertical-align: top; }
    .summary-section { width: 100%; border-top: 1px solid #000; overflow: hidden; clear: both; }
    .amount-in-words { float: left; width: 55%; padding: 10px; border-right: 1px solid #000; min-height: 50px; }
    .summary-table { float: left; width: 40%; border-collapse: collapse; }
    .footer-boxes { width: 100%; border-top: 1px solid #000; overflow: hidden; clear: both; }
    .bank-details { float: left; width: 60%; padding: 4mm; border-right: 1px solid #000; }
    .signature-box { float: left; width: 30%; padding: 4mm; text-align: center; }
    .sig-wrap { height: 60px; overflow: hidden; width: 100%; margin-bottom: 2mm; text-align: center; }
    .seal-img { width: 330px; height: auto; margin-top: -130px; margin-left: -40px; transform: rotate(5deg); }
    .terms-section { border-top: 1px solid #000; padding: 3mm 5mm; background: #f9f9f9; }
    .print-btn { display: none !important; }
';

// Setup ZIP in local temp folder
$zip = new ZipArchive();
$zipFileName = "Invoices_Bulk_" . date('Ymd_His') . ".zip";
$zipPath = __DIR__ . DIRECTORY_SEPARATOR . "temp_zips" . DIRECTORY_SEPARATOR . $zipFileName;

$tempDir = __DIR__ . DIRECTORY_SEPARATOR . "temp_zips";
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Error: Cannot create ZIP file in $tempDir - Please check folder permissions.");
}

$bulkPrintMode = true;

while ($inv = $result->fetch_assoc()) {
    $invoiceNo = $inv['invoice_no'];
    $type = $inv['type'];
    $billToName = $inv['billToName'];
    $phone = $inv['phone'];
    $email = $inv['email'];
    $gstNumber = $inv['gstNumber'];
    $address = '';
    $date = $inv['invoice_date'];
    $items = json_decode($inv['items'], true);
    $calcOriginalTotal = 0;
    $itemsHtml = "";
    if (is_array($items)) {
        foreach($items as $i => $item) {
            $itemAmt = floatval($item['amount'] ?? $item['totalInclTax'] ?? 0);
            $calcOriginalTotal += $itemAmt;
            $itemsHtml .= "<tr>
                <td style='text-align:center; white-space: nowrap;'>".($i+1)."</td>
                <td style='font-weight:bold;'>".htmlspecialchars($item['description'])."</td>
                <td style='text-align:right; font-weight:bold;'>₹".number_format($itemAmt, 2)."</td>
            </tr>";
        }
    }
    $originalTotalPayable = ($calcOriginalTotal > 0) ? $calcOriginalTotal : floatval($inv['original_total_payable']);
    $cumulativeTotalPaid = floatval($inv['cumulative_total_paid']);
    $paidThisInstallment = floatval($items[0]['paidAmt'] ?? 0);
    $previouslyPaid = $cumulativeTotalPaid - $paidThisInstallment;
    $balanceDue = $originalTotalPayable - $cumulativeTotalPaid;
    $amountInWords = numberToWords($originalTotalPayable) . ' Rupees Only';

    $finalHtml = "
    <!DOCTYPE html><html><head><style>$sharedStyle</style></head>
    <body>
        <div class='invoice-container'><div class='invoice-inner'>
            <div class='tax-invoice-label'>".($type === 'gst' ? 'TAX INVOICE' : 'INVOICE')."</div>
            <div class='header-section'>
                <div class='header-left'><img src='$logoBase64' style='width: 140px;'></div>
                <div class='header-right'>
                    <div class='company-name'>TRISHAKI TECHNOLOGIES PRIVATE LIMITED</div>
                    <div style='font-size: 9px; line-height: 1.3;'>
                        F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank, Tilakwadi, Belagavi, Karnataka - 590006<br>
                        Phone: (+91) 9980681304 | info@trishaki.com | CIN: U62010KA2025PTC213183
                    </div>
                </div>
            </div>
            <div class='billing-section'>
                <div class='bill-to-box'>
                    <div style='font-size: 8px; color: #666;'>BILL TO:</div>
                    <div style='font-weight:bold; font-size: 13px; margin: 2px 0;'>$billToName</div>
                    <div style='font-size: 10px;'>Phone: $phone</div>
                    ".($email ? "<div style='font-size: 10px;'>Email: $email</div>" : "")."
                    ".($address ? "<div style='font-size: 10px;'>Address: $address</div>" : "")."
                </div>
                <div class='invoice-info-box'>
                    <div style='font-size: 8px; color: #666;'>INVOICE INFO:</div>
                    <div style='font-size: 11px; margin-top: 5px;'><strong>Invoice No:</strong> $invoiceNo</div>
                    <div style='font-size: 11px;'><strong>Date:</strong> ".date('d-M-Y', strtotime($date))."</div>
                    <div style='font-size: 11px;'><strong>Term:</strong> ".($items[0]['paymentMode'] ?? 'Online')."</div>
                </div>
            </div>
            <table class='invoice-table'>
                <thead><tr><th style='width: 10%; text-align:center; white-space: nowrap;'>Sl No</th><th style='width: 70%;'>Description</th><th style='width: 20%; text-align:right;'>Charges</th></tr></thead>
                <tbody>$itemsHtml</tbody>
            </table>
            <div class='summary-section'>
                <div class='amount-in-words'><div style='font-size: 8px; color: #666;'>AMOUNT IN WORDS:</div><div style='font-size: 10px; margin-top: 5px; font-style: italic;'>$amountInWords</div></div>
                <div class='summary-table'>
                    <table style='width:100%; font-size: 10px; border-collapse: collapse;'>
                        ".($balanceDue <= 0.01 ? "
                        <tr><td style='padding: 2px; font-weight: bold;'>Total Amount:</td><td style='text-align:right; font-weight: bold;'>₹".number_format($originalTotalPayable, 2)."</td></tr>
                        <tr><td style='padding: 2px; font-weight: bold;'>Amount Paid:</td><td style='text-align:right; font-weight: bold;'>₹".number_format($cumulativeTotalPaid, 2)."</td></tr>
                        <tr style='background:#000; color:#fff;'><td style='padding: 3px;'><strong>Balance Due:</strong></td><td style='text-align:right;'><strong>₹0.00</strong></td></tr>
                        " : "
                        <tr><td style='padding: 2px;'>Total Amount:</td><td style='text-align:right;'>₹".number_format($originalTotalPayable, 2)."</td></tr>
                        <tr style='border-top:1px solid #000;'><td style='padding: 3px;'><strong>Current Payment Received:</strong></td><td style='text-align:right;'><strong>₹".number_format($paidThisInstallment, 2)."</strong></td></tr>
                        <tr style='background:#f2f2f2;'><td style='padding: 3px;'><strong>Total Paid Till Date:</strong></td><td style='text-align:right;'><strong>₹".number_format($cumulativeTotalPaid, 2)."</strong></td></tr>
                        <tr style='background:#000; color:#fff;'><td style='padding: 3px;'><strong>Balance Due:</strong></td><td style='text-align:right;'><strong>₹".number_format($balanceDue, 2)."</strong></td></tr>
                        ")."
                    </table>
                </div>
            </div>
            <div class='footer-boxes'>
                <div class='bank-details'>
                    <div style='font-size: 8px; color: #666; font-weight: bold; text-transform: uppercase;'>BANK DETAILS:</div>
                    <table style='width: 100%; border: none !important; border-collapse: collapse; font-size: 9px; line-height: 1.4; margin-top: 3px;'>
                        <tr>
                            <td style='width: 80px; padding: 1px 0; border: none !important; font-weight: bold;'>Bank Name</td>
                            <td style='width: 10px; padding: 1px 0; border: none !important;'>:</td>
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
                        <tr>
                            <td colspan='3' style='padding: 2px 0; border: none !important;'></td>
                        </tr>
                        <tr>
                            <td style='padding: 1px 0; border: none !important; font-weight: bold;'>UPI ID</td>
                            <td style='padding: 1px 0; border: none !important;'>:</td>
                            <td style='padding: 1px 0; border: none !important;'>paytm.s2f1szd@pty</td>
                        </tr>
                    </table>
                </div>
                <div class='signature-box'>
                    <div class='sig-wrap'>
                        <img src='$signBase64' class='seal-img'>
                    </div>
                    <div style='font-size: 9px; font-weight: bold;'>Auth. Signatory</div>
                    <div style='font-size: 8px; color: #555; margin-top: 2px; font-weight: bold;'>TriShaKi Technologies Private Limited</div>
                </div>
            </div>
            <div class='terms-section' style='font-size: 8px; font-style: italic;'>* Computer generated invoice. Fees non-refundable.</div>
        </div></div>
    </body></html>";

    try {
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($finalHtml);
        $dompdf->render();
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $invoiceNo);
        $zip->addFromString("Invoice_" . $safeName . ".pdf", $dompdf->output());
        unset($dompdf);
    } catch (Exception $e) {
        error_log("Zip Error: " . $e->getMessage());
    }
}

$zip->close();
clearstatcache();

if (!file_exists($zipPath) || filesize($zipPath) === 0) {
    die("Error: Generated ZIP is missing or empty.");
}

// Clear all buffers before headers
while (ob_get_level()) ob_end_clean();

header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($zipPath));

readfile($zipPath);
@unlink($zipPath);
exit;
