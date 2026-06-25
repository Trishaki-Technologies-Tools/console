<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = $conn->real_escape_string($_POST['category_name']);
    
    // Check if category already exists
    $checkQuery = "SELECT id FROM expenses_categories WHERE category_name = '$categoryName'";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Category already exists']);
    } else {
        $query = "INSERT INTO expenses_categories (category_name) VALUES ('$categoryName')";
        
        if ($conn->query($query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
}

$conn->close();
?>
