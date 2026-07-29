<?php
$index = file_get_contents('index.php');
$index = preg_replace('/src="js\/invoice_functions\.js[^"]*"/', 'src="js/invoice_functions.js?v=' . rand(10000, 99999) . '_' . time() . '"', $index);
file_put_contents('index.php', $index);
echo "Cache busted for invoice_functions.";
