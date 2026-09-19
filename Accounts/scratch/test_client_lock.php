<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';

// 1. Fetch or create client
$phone = '9999922222';
$conn->query("DELETE FROM clients WHERE phone = '$phone'");
$conn->query("INSERT INTO clients (name, phone, email, client_type, college_name, department) VALUES ('Original Name', '$phone', 'orig@test.com', 'Student', 'Original College', 'Original Dept')");
$origClient = $conn->query("SELECT * FROM clients WHERE phone = '$phone'")->fetch_assoc();
echo "Original Client Record:\n" . json_encode($origClient) . "\n";

// 2. Simulate saving invoice with different name/email for same phone
$postData = [
    'type' => 'non-gst',
    'billToName' => 'Different Invoice Name',
    'phone' => $phone,
    'email' => 'different@test.com',
    'date' => '2026-09-19',
    'items' => json_encode([[
        'description' => 'Test Item',
        'amount' => 500,
        'paidAmt' => 500,
        'paymentMode' => 'CASH',
        'date' => '2026-09-19'
    ]])
];

// Call save_invoice via curl or direct php execution simulation
$ch = curl_init('http://localhost/console/Accounts/api/save_invoice.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$resp = curl_exec($ch);
curl_close($ch);
echo "Save Invoice Response: $resp\n";

// 3. Verify client record was NOT modified
$updatedClient = $conn->query("SELECT * FROM clients WHERE phone = '$phone'")->fetch_assoc();
echo "Client Record After Invoice Save:\n" . json_encode($updatedClient) . "\n";

assert($updatedClient['name'] === 'Original Name', "Client name must NOT be overwritten by invoice creation");
assert($updatedClient['email'] === 'orig@test.com', "Client email must NOT be overwritten by invoice creation");
assert($updatedClient['college_name'] === 'Original College', "Client college must NOT be overwritten");

// Cleanup
$conn->query("DELETE FROM clients WHERE phone = '$phone'");
$conn->query("DELETE FROM invoices WHERE client_id = {$origClient['id']}");
$conn->query("DELETE FROM receipts WHERE client_id = {$origClient['id']}");

echo "=== CLIENT INTEGRITY TEST PASSED! ===\n";
