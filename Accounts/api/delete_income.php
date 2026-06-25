<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get attachment file path first
    $filePath = null;
    $fileQuery = "SELECT attachment FROM incomes WHERE id = $id";
    $fileResult = $conn->query($fileQuery);
    if ($fileResult && $row = $fileResult->fetch_assoc()) {
        $filePath = $row['attachment'];
    }

    $query = "DELETE FROM incomes WHERE id = $id";
    
    if ($conn->query($query)) {
        if ($filePath && file_exists('../' . $filePath)) {
            unlink('../' . $filePath);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
