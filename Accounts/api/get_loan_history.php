<?php
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_GET['loan_id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing loan_id']);
    exit;
}

$loan_id = intval($_GET['loan_id']);

try {
    $sql = "SELECT * FROM expenses WHERE loan_id = ? ORDER BY date DESC, id DESC";
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
