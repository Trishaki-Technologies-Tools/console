<?php
$content = file_get_contents('index.php');

// 1. Remove Billing Address field completely
$target_address = '<div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Billing Address</label>
                            <textarea id="newClientAddress" class="form-input" rows="3"></textarea>
                        </div>';

$content = str_replace($target_address, '', $content);
file_put_contents('index.php', $content);

// 2. Update js/app.js
$app_js = file_get_contents('js/app.js');
$app_js = str_replace(
    "data.append('address', document.getElementById('newClientAddress').value);", 
    "data.append('address', '');", 
    $app_js
);

file_put_contents('js/app.js', $app_js);

echo "Removed billing address.";
