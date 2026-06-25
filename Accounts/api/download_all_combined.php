<?php
/**
 * Combined PDF Generator - All invoices in ONE single file
 */

// Increase limits for massive PDF generation
set_time_limit(1200);
ini_set('memory_limit', '1024M');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'invoice_utils.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validation
if (!extension_loaded('gd')) {
    die("Error: PHP GD extension is required.");
}

// Fetch all invoices
$query = "SELECT i.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
          FROM invoices i 
          JOIN clients c ON i.client_id = c.id 
          ORDER BY i.id DESC 
          LIMIT 100"; // Safeguard limit
$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    die("No invoices found to combine.");
}

// Setup Dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

// Pre-load assets as Base64 one time
function safeImgToBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = @file_get_contents($path);
        if ($data) return 'data:image/'.$type.';base64,'.base64_encode($data);
    }
    return '';
}

$logoBase64 = safeImgToBase64('../assets/TRISHAKI LOGO TRANSPERANT BG.png');
$signBase64 = safeImgToBase64('../assets/ningaraj_sign_blue.png');

// Shared CSS for the combined PDF (Float-based for high stability)
$combinedStyle = '
    @page { size: A4; margin: 0; }
    body { font-family: Helvetica, sans-serif; margin: 0; padding: 0; font-size: 11px; color: #1a1a1a; background: #fff; line-height: 1.3; }
    .page-break { page-break-after: always; position: relative; width: 100%; height: 297mm; }
    .page-break:last-child { page-break-after: auto; }
    
    .invoice-container { width: 210mm; min-height: 297mm; padding: 8mm; box-sizing: border-box; }
    .invoice-inner { border: 1px solid #000; overflow: hidden; }
    .tax-invoice-label { text-align: center; font-size: 14px; font-weight: bold; text-transform: uppercase; padding: 3mm 0; border-bottom: 1px solid #000; background: #f2f2f2; letter-spacing: 2px; }
    
    /* Header Section */
    .header-section { width: 100%; border-bottom: 1px solid #000; overflow: hidden; clear: both; }
    .header-left { float: left; width: 40%; padding: 5mm; text-align: center; }
    .header-left img { width: 160px; height: auto; }
    .header-right { float: left; width: 55%; padding: 5mm; }
    .company-name { font-size: 15px; font-weight: bold; margin-bottom: 2px; color: #000; }
    .company-address { font-size: 9px; color: #4a4a4a; line-height: 1.4; }
    
    /* Billing Section */
    .billing-section { width: 100%; border-bottom: 1px solid #000; overflow: hidden; clear: both; }
    .bill-to-box { float: left; width: 50%; padding: 4mm; border-right: 1px solid #000; min-height: 80px; }
    .invoice-info-box { float: left; width: 45%; padding: 4mm; min-height: 80px; }
    .section-title { font-size: 8px; font-weight: bold; color: #4a4a4a; text-transform: uppercase; margin-bottom: 2px; letter-spacing: 1px; }
    .billing-content .name { font-size: 13px; font-weight: bold; margin-bottom: 1mm; }
    
    /* Table Section */
    .table-section { width: 100%; min-height: 350px; }
    .invoice-table { width: 100%; border-collapse: collapse; }
    .invoice-table th { background: #f2f2f2; border-bottom: 1px solid #000; border-right: 1px solid #000; padding: 6px 8px; font-size: 9px; text-align: left; }
    .invoice-table td { padding: 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; vertical-align: top; font-size: 10px; }
    .invoice-table tr:last-child td { border-bottom: none; }
    .invoice-table td:last-child, .invoice-table th:last-child { border-right: none; }
    
    /* Summary Section */
    .summary-section { width: 100%; border-top: 1px solid #000; overflow: hidden; clear: both; }
    .amount-in-words { float: left; width: 60%; padding: 4mm; border-right: 1px solid #000; min-height: 40px; font-style: italic; font-size: 10px; }
    .summary-table-box { float: left; width: 39%; }
    .summary-table { width: 100%; border-collapse: collapse; }
    .summary-table td { padding: 6px 8px; border-bottom: 1px solid #000; border-right: 1px solid #000; font-size: 10px; }
    .total-row { font-weight: bold; background: #f2f2f2; font-size: 11px; }
    
    /* Footer */
    .footer-boxes { width: 100%; border-top: 1px solid #000; overflow: hidden; clear: both; }
    .bank-details { float: left; width: 65%; padding: 3mm; border-right: 1px solid #000; font-size: 9px; }
    .signature-box { float: left; width: 34%; padding: 3mm; text-align: center; }
    .sig-wrap { height: 60px; overflow: hidden; width: 100%; margin-bottom: 2mm; text-align: center; }
    .seal-img { width: 330px; height: auto; margin-top: -130px; margin-left: -40px; transform: rotate(5deg); }
    .sig-line { font-size: 9px; font-weight: bold; text-transform: uppercase; margin-top: 3mm; }
    
    .terms-section { border-top: 1px solid #000; padding: 3mm 4mm; background: #f9f9f9; font-size: 8.5px; font-style: italic; color: #4a4a4a; clear: both; }
    .print-btn { display: none !important; }
';

// Start Building HTML
$fullHtml = "<!DOCTYPE html><html><head><meta charset='UTF-8'><style>$combinedStyle</style></head><body>";

$bulkPrintMode = true; // Signals generate_invoice.php to only output the body content

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
    $originalTotalPayable = $inv['original_total_payable'];
    $cumulativeTotalPaid = $inv['cumulative_total_paid'];
    
    // Capture individual invoice body
    ob_start();
    include 'generate_invoice.php';
    $bodyHtml = ob_get_clean();
    
    // Clean bodyHtml: Strip style and script tags if any were leaked
    $bodyHtml = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $bodyHtml);
    $bodyHtml = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $bodyHtml);
    
    $fullHtml .= "<div class='page-break'>$bodyHtml</div>";
}

$fullHtml .= "</body></html>";

// Map image paths to Base64 in bulk
$fullHtml = str_replace(
    ['../assets/TRISHAKI LOGO TRANSPERANT BG.png', '../assets/TRISHAKI%20LOGO%20TRANSPERANT%20BG.png', '../assets/ningaraj_sign_blue.png'],
    [$logoBase64, $logoBase64, $signBase64],
    $fullHtml
);

// Dompdf Rendering
try {
    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($fullHtml);
    $dompdf->render();
    
    $filename = "Invoices_Combined_Batch_" . date('Ymd_His') . ".pdf";
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo $dompdf->output();
    exit;
} catch (Exception $e) {
    die("Combined PDF Generation Error: " . $e->getMessage());
}
