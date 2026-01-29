<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $sql = "SELECT * FROM salary_logs ORDER BY payment_date DESC";
    $result = $conn->query($sql);

    $logs = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    echo json_encode($logs);

} catch (Throwable $e) {
    http_response_code(500); // Return 500 status
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
