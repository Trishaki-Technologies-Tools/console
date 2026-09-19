<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/fy_helper.php';

$fy = getFinancialYearDates();

$stmt = $conn->prepare("
    SELECT 
        c.id,
        c.name,
        c.phone,
        c.email,
        c.gst_number,
        c.address,
        c.client_type,
        c.college_name,
        c.department,
        COUNT(i.id) as invoice_count,
        MAX(i.created_at) as last_invoice_date
    FROM clients c
    LEFT JOIN invoices i ON c.id = i.client_id AND ((i.invoice_date >= ? AND i.invoice_date <= ?) OR (i.invoice_date IS NULL AND DATE(i.created_at) >= ? AND DATE(i.created_at) <= ?))
    WHERE c.created_at >= ? AND c.created_at <= ?
    GROUP BY c.id
    ORDER BY last_invoice_date DESC, c.id DESC
");
$stmt->bind_param("ssssss", $fy['start_date'], $fy['end_date'], $fy['start_date'], $fy['end_date'], $fy['start_datetime'], $fy['end_datetime']);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row;
}
echo "Clients returned: " . count($clients) . "\n";
foreach($clients as $c) {
    if ($c['id'] == 269 || $c['invoice_count'] > 0) {
        echo "Client ID: {$c['id']}, Name: {$c['name']}, Invoices Count: {$c['invoice_count']}\n";
    }
}
