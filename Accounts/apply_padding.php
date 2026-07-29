<?php
// Read the file safely as raw bytes
$content = file_get_contents('index.php');

// We will inject `.page-content { padding: 32px 40px !important; }` into our fallback style block
$target = '/* Fallback Layout Spacing Fixes */';
$replacement = '/* Fallback Layout Spacing Fixes */
        .page-content { padding: 32px 40px !important; }';

$content = str_replace($target, $replacement, $content);

file_put_contents('index.php', $content);
echo "Added padding to page-content!";
