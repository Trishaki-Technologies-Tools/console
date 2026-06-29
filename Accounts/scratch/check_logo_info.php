<?php
$path = 'c:\xampp\htdocs\console\Console\Accounts\assets\TRISHAKI ROUND TRANSPERANT BG.png';
if (!file_exists($path)) {
    die("File does not exist: $path\n");
}
$info = getimagesize($path);
echo "Width: " . $info[0] . "\n";
echo "Height: " . $info[1] . "\n";
echo "Mime: " . $info['mime'] . "\n";
