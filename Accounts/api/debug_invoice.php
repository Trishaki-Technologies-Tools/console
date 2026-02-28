<?php
// Debug version to see what data is being received
header('Content-Type: text/plain');

echo "=== DEBUG INVOICE DATA ===\n\n";

echo "GET Parameters:\n";
print_r($_GET);

echo "\n\nParsed Values:\n";
$type = isset($_GET['type']) ? $_GET['type'] : 'non-gst';
echo "Type: $type\n";

$items = [];
if (isset($_GET['items'])) {
    $items = json_decode($_GET['items'], true);
}

echo "\nItems JSON:\n";
echo $_GET['items'] ?? 'NO ITEMS';

echo "\n\nParsed Items:\n";
print_r($items);

echo "\n\nCalculations:\n";
$totalAmount = 0;
$totalPaid = 0;
$grandTotal = 0;

if ($type === 'gst') {
    foreach ($items as $item) {
        $totalInclTax = floatval($item['totalInclTax'] ?? 0);
        $paidAmt = floatval($item['paidAmt'] ?? 0);
        echo "Item: totalInclTax=$totalInclTax, paidAmt=$paidAmt\n";
        $grandTotal += $totalInclTax;
        $totalPaid += $paidAmt;
    }
} else {
    foreach ($items as $item) {
        $totalAmt = floatval($item['amount'] ?? 0);
        $paidAmt = floatval($item['paidAmt'] ?? $item['amount'] ?? 0);
        echo "Item: amount=$totalAmt, paidAmt=$paidAmt\n";
        $totalAmount += $totalAmt;
        $totalPaid += $paidAmt;
    }
    $grandTotal = $totalAmount;
}

echo "\nFinal Totals:\n";
echo "grandTotal: $grandTotal\n";
echo "totalPaid: $totalPaid\n";
echo "totalAmount: $totalAmount\n";

$originalTotalPayable = isset($_GET['originalTotalPayable']) ? floatval($_GET['originalTotalPayable']) : null;
$cumulativeTotalPaid = isset($_GET['cumulativeTotalPaid']) ? floatval($_GET['cumulativeTotalPaid']) : null;

echo "\noriginalTotalPayable: " . ($originalTotalPayable === null ? 'NULL' : $originalTotalPayable) . "\n";
echo "cumulativeTotalPaid: " . ($cumulativeTotalPaid === null ? 'NULL' : $cumulativeTotalPaid) . "\n";

if ($originalTotalPayable !== null && $cumulativeTotalPaid !== null) {
    $cumulativeGrandTotal = $originalTotalPayable;
    $cumulativePaid = floatval($cumulativeTotalPaid) + $totalPaid;
} else {
    $cumulativeGrandTotal = $grandTotal;
    $cumulativePaid = $totalPaid;
}

echo "\nDisplay Values:\n";
echo "cumulativeGrandTotal (Total Payable): $cumulativeGrandTotal\n";
echo "cumulativePaid (Total Paid): $cumulativePaid\n";
echo "dueAmount (Balance Due): " . ($cumulativeGrandTotal - $cumulativePaid) . "\n";
?>
