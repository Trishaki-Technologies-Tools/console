<?php
$index = file_get_contents('index.php');
$pattern = '/<div class="generate-invoice-container" style="position: relative; display: inline-block;">.*?<\/button>\s*<\/div>\s*<\/div>/s';
$index = preg_replace($pattern, '', $index);
file_put_contents('index.php', $index);

$app = file_get_contents('js/app.js');

// Replace the Actions td in app.js
$actions_pattern = '/<button class="btn-action btn-delete-small" onclick="deleteClient\(\$\{client\.id\}, \'([^`]+)\'\)" title="Delete Client" style="([^"]+)">.*?<\/button>/s';
$new_actions = '
                                <button class="btn-action btn-create-invoice" onclick="openInvoiceForClient(\'${escapeHtml(client.name)}\', \'${client.phone}\', \'${client.email}\')" title="Create Invoice" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.15); color: #10b981; padding: 6px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; margin-right: 6px;">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </button>
                                <button class="btn-action btn-delete-small" onclick="deleteClient(${client.id}, \'${escapeHtml(client.name)}\')" title="Delete Client" style="$2">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                    </svg>
                                </button>';

$app = preg_replace($actions_pattern, $new_actions, $app);

// Add the helper function to the end of app.js
$helper = "
window.openInvoiceForClient = function(name, phone, email) {
    if(typeof switchToNonGstModal === 'function') {
        switchToNonGstModal();
        setTimeout(() => {
            const safeEmail = email && email !== 'N/A' && email !== 'null' ? email : '';
            if(document.getElementById('nonGstBillToName')) document.getElementById('nonGstBillToName').value = name;
            if(document.getElementById('nonGstPhone')) document.getElementById('nonGstPhone').value = phone;
            if(document.getElementById('nonGstEmail')) document.getElementById('nonGstEmail').value = safeEmail;
            
            if(document.getElementById('gstBillToName')) document.getElementById('gstBillToName').value = name;
            if(document.getElementById('gstPhone')) document.getElementById('gstPhone').value = phone;
            if(document.getElementById('gstEmail')) document.getElementById('gstEmail').value = safeEmail;
        }, 50);
    } else {
        alert('Invoice functions are not loaded yet.');
    }
};
";
$app .= $helper;

file_put_contents('js/app.js', $app);

echo "Modified index and app.js";
