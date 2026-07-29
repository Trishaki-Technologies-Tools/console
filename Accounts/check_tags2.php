<?php
$c = file_get_contents('index_test.php');
echo "index_test.php:\n";
echo "<div : " . substr_count($c, '<div') . "\n";
echo "</div> : " . substr_count($c, '</div>') . "\n";
