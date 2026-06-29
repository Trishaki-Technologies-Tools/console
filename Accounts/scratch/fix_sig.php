<?php
$file = 'api/print_quotation.php';
$lines = file($file);

// Verify that index 528 contains "</div>"
if (trim($lines[528]) === '</div>') {
    unset($lines[528]);
    file_put_contents($file, implode('', $lines));
    echo "Successfully removed the extra closing div!\n";
} else {
    echo "Index 528 did not contain expected closing div: " . $lines[528] . "\n";
}
