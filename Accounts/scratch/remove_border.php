<?php
$path = 'c:\xampp\htdocs\console\Console\Accounts\assets\TRISHAKI ROUND TRANSPERANT BG.png';
$im = imagecreatefrompng($path);
$w = imagesx($im);
$h = imagesy($im);
$cx = $w / 2;
$cy = $h / 2;

// Create a new image of the same size with a white background
$new_im = imagecreatetruecolor($w, $h);
$white = imagecolorallocate($new_im, 255, 255, 255);
imagefill($new_im, 0, 0, $white);

// Enable alpha blending for drawing the logo on top of the white background
imagealphablending($new_im, true);

// Loop through pixels and copy only if they are within a radius of 1350 pixels from the center
for ($y = 0; $y < $h; $y++) {
    for ($x = 0; $x < $w; $x++) {
        $dx = $x - $cx;
        $dy = $y - $cy;
        $dist = sqrt($dx*$dx + $dy*$dy);
        
        // If it is inside the border area
        if ($dist <= 1350) {
            $rgb = imagecolorat($im, $x, $y);
            $colors = imagecolorsforindex($im, $rgb);
            // Blend the transparent pixel onto white
            $alpha = $colors['alpha']; // 0 = opaque, 127 = fully transparent in GD
            if ($alpha < 127) {
                // Calculate blended color
                $pct = (127 - $alpha) / 127;
                $r = (int)($colors['red'] * $pct + 255 * (1 - $pct));
                $g = (int)($colors['green'] * $pct + 255 * (1 - $pct));
                $b = (int)($colors['blue'] * $pct + 255 * (1 - $pct));
                $col = imagecolorallocate($new_im, $r, $g, $b);
                imagesetpixel($new_im, $x, $y, $col);
            }
        }
    }
}

// Save the new image
$dest = 'c:\xampp\htdocs\console\Console\Accounts\assets\trishaki_round_logo_no_border.png';
imagepng($new_im, $dest);
imagedestroy($im);
imagedestroy($new_im);
echo "Successfully created borderless logo with solid white background!\n";
