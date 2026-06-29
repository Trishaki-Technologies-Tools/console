<?php
$path = 'c:\xampp\htdocs\console\Console\Accounts\assets\TRISHAKI ROUND TRANSPERANT BG.png';
$im = imagecreatefrompng($path);
$w = imagesx($im);
$h = imagesy($im);
$cy = (int)($h / 2);

echo "Scanning row $cy from x=0 to $w...\n";
$detected = [];
for ($x = 0; $x < $w; $x++) {
    $rgb = imagecolorat($im, $x, $cy);
    $colors = imagecolorsforindex($im, $rgb);
    // If alpha is not fully transparent and it is dark/black
    if ($colors['alpha'] < 100 && $colors['red'] < 50 && $colors['green'] < 50 && $colors['blue'] < 50) {
        $detected[] = $x;
    }
}

echo "Detected dark pixels at columns: " . implode(', ', array_slice($detected, 0, 20)) . " ... " . implode(', ', array_slice($detected, -20)) . "\n";
echo "Total detected: " . count($detected) . "\n";
