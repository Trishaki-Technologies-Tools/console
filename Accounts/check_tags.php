<?php
$c = file_get_contents('index.php');
echo "<!-- : " . substr_count($c, '<!--') . "\n";
echo "--> : " . substr_count($c, '-->') . "\n";
echo "<style : " . substr_count($c, '<style') . "\n";
echo "</style> : " . substr_count($c, '</style>') . "\n";
echo "<div : " . substr_count($c, '<div') . "\n";
echo "</div> : " . substr_count($c, '</div>') . "\n";
