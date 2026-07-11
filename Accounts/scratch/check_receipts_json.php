<?php
require_once 'api/config.php';
$res = $conn->query('SELECT id, receipt_no, items FROM receipts');
while($row = $res->fetch_assoc()) {
    if (empty($row['items'])) {
        echo "Empty items for receipt " . $row['receipt_no'] . " (ID: " . $row['id'] . ")\n";
        continue;
    }
    json_decode($row['items']);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Invalid JSON in Receipt: " . $row['receipt_no'] . " (ID: " . $row['id'] . ") -> Error: " . json_last_error_msg() . "\n";
    }
}
?>
