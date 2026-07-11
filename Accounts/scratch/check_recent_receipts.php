<?php
require_once __DIR__ . '/../api/config.php';

$res = $conn->query("SELECT r.id, r.receipt_no, c.name, r.items, r.created_at FROM receipts r JOIN clients c ON r.client_id = c.id ORDER BY r.id DESC LIMIT 15");
while ($r = $res->fetch_assoc()) {
    echo $r['id'] . " | " . $r['receipt_no'] . " | " . $r['name'] . " | " . $r['items'] . " | " . $r['created_at'] . "\n";
}
?>
