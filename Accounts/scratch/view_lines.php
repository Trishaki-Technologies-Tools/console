<?php
$lines = file('api/print_quotation.php');
for($i=520; $i<=535; $i++) {
    echo ($i+1) . ': ' . bin2hex($lines[$i]) . ' | ' . $lines[$i];
}
