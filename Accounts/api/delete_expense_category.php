<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Check if category is being used
    $categoryQuery = "SELECT category_name FROM expense_categories WHERE id = $id";
    $categoryResult = $conn->query($categoryQuery);
    
    if ($categoryResult->num_rows > 0) {
        $category = $categoryResult->fetch_assoc()['category_name'];
        
        // Check if any expenses use this category
        $usageQuery = "SELECT COUNT(*) as count FROM expenses WHERE category = '$category'";
        $usageResult = $conn->query($usageQuery);
        $usageCount = $usageResult->fetch_assoc()['count'];
        
        if ($usageCount > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete category that is in use']);
        } else {
            $query = "DELETE FROM expense_categories WHERE id = $id";
            
            if ($conn->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Category not found']);
    }
}

$conn->close();
?>
