<?php
$content = file_get_contents('index.php');

// Bump cache for app.js and invoice_functions.js
$content = preg_replace('/js\/app\.js\?v=\d+/', 'js/app.js?v=' . time(), $content);
$content = preg_replace('/js\/invoice_functions\.js\?v=\d+/', 'js/invoice_functions.js?v=' . time(), $content);

file_put_contents('index.php', $content);
echo "Cache bumped.";
