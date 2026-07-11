<?php
require_once __DIR__ . '/../api/config.php';

// Fetch the sum of original_total_payable and cumulative_total_paid for invoices generated in 2026
$res = $conn->query("SELECT COUNT(*) as count, SUM(original_total_payable) as total_payable, SUM(cumulative_total_paid) as total_paid FROM invoices WHERE invoice_no LIKE 'TSK-2026-%'");
$row = $res->fetch_assoc();
echo "Invoices count: " . $row['count'] . "\n";
echo "Total Payable: " . $row['total_payable'] . "\n";
echo "Total Paid: " . $row['total_paid'] . "\n";

// Let's list each invoice with its amount and paid
$res2 = $conn->query("SELECT invoice_no, original_total_payable, cumulative_total_paid FROM invoices WHERE invoice_no LIKE 'TSK-2026-%' ORDER BY invoice_no ASC");
while($inv = $res2->fetch_assoc()) {
    echo "  " . $inv['invoice_no'] . ": Payable=" . $inv['original_total_payable'] . ", Paid=" . $inv['cumulative_total_paid'] . "\n";
}
?>
