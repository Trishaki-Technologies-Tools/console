<?php
$content = file_get_contents('js/invoice_functions.js');

$content = preg_replace('/function switchToGstModal\(\)\s*\{.*?document\.getElementById\(\'gstInvoiceModal\'\)\.classList\.add\(\'show\'\);/s', '$0
    if (typeof loadPaymentModesDropdowns === \'function\') loadPaymentModesDropdowns();', $content);

$content = preg_replace('/function switchToNonGstModal\(\)\s*\{.*?document\.getElementById\(\'nonGstInvoiceModal\'\)\.classList\.add\(\'show\'\);/s', '$0
    if (typeof loadPaymentModesDropdowns === \'function\') loadPaymentModesDropdowns();', $content);

file_put_contents('js/invoice_functions.js', $content);
echo "Updated invoice_functions.js with regex";
