<?php
$file = 'api/print_quotation.php';
$content = file_get_contents($file);
$content = str_replace('height: 45px;', 'height: 65px;', $content, $count);
file_put_contents($file, $content);
echo "Replaced $count occurrences\n";
