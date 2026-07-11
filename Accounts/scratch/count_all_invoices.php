<?php
require_once __DIR__ . '/../api/config.php';

$res = $conn->query("SELECT COUNT(*) as count, SUM(original_total_payable) as total_payable, SUM(cumulative_total_paid) as total_paid FROM invoices");
$row = $res->fetch_assoc();
echo "All Invoices in DB:\n";
echo "Count: " . $row['count'] . "\n";
echo "Total Payable: " . $row['total_payable'] . "\n";
echo "Total Paid: " . $row['total_paid'] . "\n";
?>
