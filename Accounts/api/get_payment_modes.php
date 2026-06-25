<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $res = $conn->query("SELECT * FROM payment_modes");
    $modes = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $modes[] = $row;
        }
    }
    echo json_encode($modes);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>
