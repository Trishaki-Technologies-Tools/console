<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $stmt = $conn->prepare("DELETE FROM quotations WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            log_action($conn, 'DELETE', 'quotations', $id, "Deleted quotation ID: $id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing quotation ID']);
}
?>
