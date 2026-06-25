<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mode_name']) && trim($_POST['mode_name']) !== '') {
        $modeName = $conn->real_escape_string(trim($_POST['mode_name']));
        
        // Check if payment mode already exists
        $checkQuery = "SELECT id FROM payment_modes WHERE mode_name = '$modeName'";
        $checkResult = $conn->query($checkQuery);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Payment mode already exists']);
        } else {
            $query = "INSERT INTO payment_modes (mode_name) VALUES ('$modeName')";
            
            if ($conn->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Payment mode name is required']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
