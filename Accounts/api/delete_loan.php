<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
        
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Loan deleted successfully']);
        } else {
             throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing ID parameter']);
}

$conn->close();
?>
