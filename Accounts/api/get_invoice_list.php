<?php
/**
 * Simple API to return a list of all invoice numbers
 */
header('Content-Type: application/json');
require_once 'config.php';

try {
    $result = $conn->query("SELECT invoice_no FROM invoices ORDER BY id DESC LIMIT 500");
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $list[] = $row['invoice_no'];
    }
    echo json_encode($list);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
