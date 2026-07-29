<?php
// 1. Update index.php
$index = file_get_contents('index.php');

$old_select = '<div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label">Type <span class="required">*</span></label>
                        <select id="newClientType" class="form-input" onchange="toggleClientFields()">
                            <option value="Client">Client</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>';

$new_capsules = '<div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" style="display: block; margin-bottom: 8px;">Record Type</label>
                        <div class="ledger-tabs" style="display: flex; background: #1e293b; padding: 4px; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; gap: 4px;">
                            <button type="button" class="ledger-tab-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')" style="border: none; flex: 1; text-align: center; color: white;">Client</button>
                            <button type="button" class="ledger-tab-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')" style="border: none; flex: 1; text-align: center; color: white;">Student</button>
                        </div>
                        <input type="hidden" id="newClientType" value="Client">
                    </div>';

$index = str_replace($old_select, $new_capsules, $index);
file_put_contents('index.php', $index);

// 2. Update js/app.js
$app_js = file_get_contents('js/app.js');

$new_js_func = "
function setClientType(type) {
    document.getElementById('newClientType').value = type;
    
    if (type === 'Student') {
        document.getElementById('btnTypeStudent').classList.add('active');
        document.getElementById('btnTypeClient').classList.remove('active');
    } else {
        document.getElementById('btnTypeClient').classList.add('active');
        document.getElementById('btnTypeStudent').classList.remove('active');
    }
    
    toggleClientFields();
}
";

if (strpos($app_js, 'function setClientType') === false) {
    $app_js .= "\n" . $new_js_func;
}

// Also need to reset capsule UI when closing the modal
$old_close_modal = "function closeAddClientModal() {
    document.getElementById('addClientModal').classList.remove('show');
    document.getElementById('addClientForm').reset();
}";

$new_close_modal = "function closeAddClientModal() {
    document.getElementById('addClientModal').classList.remove('show');
    document.getElementById('addClientForm').reset();
    if(typeof setClientType === 'function') setClientType('Client');
}";

$app_js = str_replace($old_close_modal, $new_close_modal, $app_js);

file_put_contents('js/app.js', $app_js);

echo "Updated to use capsules.";
