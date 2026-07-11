<?php
require_once __DIR__ . '/../api/config.php';

$res = $conn->query("SELECT COUNT(*) as count FROM clients WHERE email LIKE '%@example.com'");
$row = $res->fetch_assoc();
echo "Clients with placeholder email: " . $row['count'] . "\n";

if ($row['count'] > 0) {
    $conn->query("UPDATE clients SET email = '' WHERE email LIKE '%@example.com'");
    echo "Successfully cleared placeholder emails for " . $conn->affected_rows . " clients.\n";
}
?>
