<?php
$content = file_get_contents('index.php');

// 1. Change Record Type to Client Type
$content = str_replace('<label class="form-label" style="display: block; margin-bottom: 8px;">Record Type</label>',
                       '<label class="form-label" style="display: block; margin-bottom: 8px;">Client Type</label>', 
                       $content);

// 2. Fix Capsule Styles robustly
$old_capsules = '<div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px;">
                            <button type="button" class="ledger-tab-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')" style="border: none; flex: 1; text-align: center;">Client</button>
                            <button type="button" class="ledger-tab-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')" style="border: none; flex: 1; text-align: center;">Student</button>
                        </div>';

// We will add an inline style block inside the modal to properly handle the active state text color in dark mode
$new_capsules = '<style>
                            .client-type-btn { border: none; padding: 8px; border-radius: 6px; font-weight: 500; cursor: pointer; flex: 1; text-align: center; background: transparent; color: #94a3b8; transition: all 0.2s; }
                            .client-type-btn.active { background: #3b82f6; color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                        </style>
                        <div style="display: flex; background: #0f172a; padding: 4px; border: 1px solid #334155; border-radius: 8px; gap: 4px;">
                            <button type="button" class="client-type-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')">Client</button>
                            <button type="button" class="client-type-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')">Student</button>
                        </div>';

// Since the old script might have failed to replace `#1e293b` earlier due to caching or duplicate script runs, 
// let's do a regex replace to catch whatever is currently there.

$pattern = '/<div class="ledger-tabs" style="display: flex;[^>]+>.*?<\/button>\s*<\/div>/s';
$content = preg_replace($pattern, $new_capsules, $content);

file_put_contents('index.php', $content);

// 3. Update js/app.js to toggle the new class names
$app_js = file_get_contents('js/app.js');
// Wait, js/app.js already does `.classList.add('active')`. The class name of the buttons changed, but JS uses IDs `btnTypeClient` and `btnTypeStudent`, so `classList.add('active')` will still work perfectly on `.client-type-btn`!

echo "Capsules updated with custom robust styling.";
