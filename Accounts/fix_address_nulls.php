<?php
$content = file_get_contents('js/invoice_functions.js');

$replacements = [
    "document.getElementById('gstAddress').value = invoice.address || '';" => "if (document.getElementById('gstAddress')) document.getElementById('gstAddress').value = invoice.address || '';",
    
    "document.getElementById('nonGstAddress').value = invoice.address || '';" => "if (document.getElementById('nonGstAddress')) document.getElementById('nonGstAddress').value = invoice.address || '';",
    
    "document.getElementById('nonGstAddress').value = lastInvoice.address || '';" => "if (document.getElementById('nonGstAddress')) document.getElementById('nonGstAddress').value = lastInvoice.address || '';",
    
    "document.getElementById('gstAddress').value = lastInvoiceRecord.address || '';" => "if (document.getElementById('gstAddress')) document.getElementById('gstAddress').value = lastInvoiceRecord.address || '';",
    
    "document.getElementById('nonGstAddress').value = lastInvoiceRecord.address || '';" => "if (document.getElementById('nonGstAddress')) document.getElementById('nonGstAddress').value = lastInvoiceRecord.address || '';",
    
    "address: document.getElementById('gstAddress').value," => "address: document.getElementById('gstAddress') ? document.getElementById('gstAddress').value : '',",
    
    "address: document.getElementById('nonGstAddress').value || ''," => "address: document.getElementById('nonGstAddress') ? document.getElementById('nonGstAddress').value : '',"
];

$content = str_replace(array_keys($replacements), array_values($replacements), $content);

file_put_contents('js/invoice_functions.js', $content);
echo "Fixed null reference errors for address fields.";
