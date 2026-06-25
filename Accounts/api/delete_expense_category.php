<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Check if any expenses use this category_id
        $usageQuery = "SELECT COUNT(*) as count FROM expenses WHERE category_id = $id";
        $usageResult = $conn->query($usageQuery);
        $usageCount = $usageResult->fetch_assoc()['count'];
        
        if ($usageCount > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete category that is in use']);
        } else {
            $query = "DELETE FROM expenses_categories WHERE id = $id";
            
            if ($conn->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No ID provided']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
