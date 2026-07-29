<?php
$index = file_get_contents('index.php');
$index = preg_replace('/src="js\/app\.js[^"]*"/', 'src="js/app.js?v=' . rand(10000, 99999) . '_' . time() . '"', $index);
file_put_contents('index.php', $index);
echo "Cache busted.";
