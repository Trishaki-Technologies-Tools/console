<?php
$content = file_get_contents('index.php');

// 1. Remove the dropdown from the button and change onclick
$btn_old = '<button class="btn-primary" onclick="showInvoiceTypeSelection()">+ Generate
                                    Invoice</button>
                                <!-- Invoice Type Selection Dropdown -->
                                <div id="invoiceTypeSelectionDiv"';

$btn_new = '<button class="btn-primary" onclick="openUnifiedInvoiceModal()">+ Generate Invoice</button>
                                <!-- Invoice Type Selection Dropdown removed -->
                                <div id="invoiceTypeSelectionDiv" style="display:none;"';

$content = str_replace($btn_old, $btn_new, $content);

// 2. We need to wrap the two modal contents into a unified modal.
// We will rename `nonGstInvoiceModal` and `gstInvoiceModal` and wrap them.
// Wait, an easier way is to just inject a CSS/JS tab switcher that runs inside the modal!
// Actually, it's safer to just inject a brand new Unified Modal at the end of the file,
// But the forms have IDs that JS relies on. 

file_put_contents('index.php', $content);
echo "Button updated. Now I need to build the unified modal.";
