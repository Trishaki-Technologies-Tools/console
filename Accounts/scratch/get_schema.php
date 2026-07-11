<?php
require_once __DIR__ . '/../api/config.php';

$res = $conn->query("DESCRIBE invoices");
if ($res) {
    echo "INVOICES TABLE:\n";
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error describing invoices: " . $conn->error . "\n";
}

$res = $conn->query("SHOW TABLES");
if ($res) {
    echo "\nALL TABLES:\n";
    while ($row = $res->fetch_row()) {
        echo $row[0] . "\n";
    }
}
?>
