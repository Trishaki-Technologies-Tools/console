<?php
require_once __DIR__ . '/../api/config.php';

// List of students with Cash in Excel:
// 1. Namrata patne - 8867491082
// 2. Shraddha Dodamani - 6360711038
// 3. Meghraj jadhav - 9886849716
// 4. Shubham S Shivarayakar - 9838619570
// 5. Adarsh Dhanapal Dooge - 8088099184
// 6. Bhuvan Deepak Jadhav - 9742806939
// 7. Akshay Vittal Sutar - 9972210972
// 8. Sujal Kalkhambkar - 7026033270
// 9. Prathamesh Manoj Gunjati - 9421775058
// 10. Megha kamal - 9538791008
// 11. Shrusti Bhadrakali - 7619160587

$res = $conn->query("
    SELECT r.receipt_no, r.invoice_no, c.name, c.phone, r.items, r.cumulative_total_paid
    FROM receipts r
    JOIN clients c ON r.client_id = c.id
");

echo "Database receipts with details:\n";
while ($row = $res->fetch_assoc()) {
    $items = json_decode($row['items'], true);
    $mode = 'N/A';
    if (is_array($items) && isset($items[0]['paymentMode'])) {
        $mode = $items[0]['paymentMode'];
    }
    
    // We only care about the names listed or those with Cash/Online discrepancies
    echo "Receipt: {$row['receipt_no']} | Name: {$row['name']} | Phone: {$row['phone']} | Paid: {$row['cumulative_total_paid']} | Mode: {$mode}\n";
}
?>
