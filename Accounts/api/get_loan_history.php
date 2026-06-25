<?php
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_GET['loan_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing loan_id']);
    exit;
}

$loan_id = intval($_GET['loan_id']);

try {
    // Check which column name is used for payment type
    $cols = [];
    $cr = $conn->query("SHOW COLUMNS FROM loans_payments");
    while ($c = $cr->fetch_assoc()) $cols[] = $c['Field'];
    $typeCol = in_array('payment_type', $cols) ? 'payment_type' : 'type';

    $sql = "SELECT payment_date AS date, {$typeCol} AS type, amount
            FROM loans_payments
            WHERE loan_id = ?
            ORDER BY payment_date DESC, id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }

    echo json_encode(['success' => true, 'history' => $history]);
    $stmt->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
