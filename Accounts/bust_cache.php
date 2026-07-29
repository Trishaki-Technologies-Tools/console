<?php
$content = file_get_contents('index.php');

$target = '<script src="js/invoice_functions.js"></script>';
$rep = '<script src="js/invoice_functions.js?v=<?= time() ?>"></script>';

$content = str_replace($target, $rep, $content);

file_put_contents('index.php', $content);
echo "Added cache buster.";
