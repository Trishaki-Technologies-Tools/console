<?php
$content = file_get_contents('index.php');

// 1. Change title back to Add New Client
$content = str_replace('<h3>Add New Record</h3>', '<h3>Add New Client</h3>', $content);

// 2. Fix the capsules color
$bad_client_capsule = '<button type="button" class="ledger-tab-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')" style="border: none; flex: 1; text-align: center; color: white;">Client</button>';
$good_client_capsule = '<button type="button" class="ledger-tab-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')" style="border: none; flex: 1; text-align: center;">Client</button>';

$bad_student_capsule = '<button type="button" class="ledger-tab-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')" style="border: none; flex: 1; text-align: center; color: white;">Student</button>';
$good_student_capsule = '<button type="button" class="ledger-tab-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')" style="border: none; flex: 1; text-align: center;">Student</button>';

$content = str_replace($bad_client_capsule, $good_client_capsule, $content);
$content = str_replace($bad_student_capsule, $good_student_capsule, $content);

// 3. Remove the inline background of the container since .ledger-tabs usually has its own styling
$bad_container = '<div class="ledger-tabs" style="display: flex; background: #1e293b; padding: 4px; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; gap: 4px;">';
$good_container = '<div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px;">';
$content = str_replace($bad_container, $good_container, $content);

file_put_contents('index.php', $content);
echo "Fixed capsule UI.";
