<?php
$content = file_get_contents('js/invoice_functions.js');

$search1 = "function switchToGstModal() {
    document.getElementById('nonGstInvoiceModal').classList.remove('show');
    document.getElementById('gstInvoiceModal').classList.add('show');
}";

$replace1 = "function switchToGstModal() {
    document.getElementById('nonGstInvoiceModal').classList.remove('show');
    document.getElementById('gstInvoiceModal').classList.add('show');
    if (typeof loadPaymentModesDropdowns === 'function') loadPaymentModesDropdowns();
}";

$search2 = "function switchToNonGstModal() {
    document.getElementById('gstInvoiceModal').classList.remove('show');
    document.getElementById('nonGstInvoiceModal').classList.add('show');
}";

$replace2 = "function switchToNonGstModal() {
    document.getElementById('gstInvoiceModal').classList.remove('show');
    document.getElementById('nonGstInvoiceModal').classList.add('show');
    if (typeof loadPaymentModesDropdowns === 'function') loadPaymentModesDropdowns();
}";

$content = str_replace(str_replace("\r\n", "\n", $search1), $replace1, $content);
$content = str_replace(str_replace("\r\n", "\n", $search2), $replace2, $content);

// For CRLF robustness
$content = str_replace($search1, $replace1, $content);
$content = str_replace($search2, $replace2, $content);

// And another one for openUnifiedInvoiceModal
$search3 = "function openUnifiedInvoiceModal() {
    // Default open GST modal
    document.getElementById('gstInvoiceModal').classList.add('show');
}";

$replace3 = "function openUnifiedInvoiceModal() {
    // Default open GST modal
    document.getElementById('gstInvoiceModal').classList.add('show');
    if (typeof loadPaymentModesDropdowns === 'function') loadPaymentModesDropdowns();
}";
$content = str_replace($search3, $replace3, $content);

file_put_contents('js/invoice_functions.js', $content);

echo "Updated invoice_functions.js";
