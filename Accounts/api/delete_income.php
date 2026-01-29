<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "DELETE FROM incomes WHERE id = $id";
    
    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
