<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mode_name']) && trim($_POST['mode_name']) !== '') {
        $modeName = $conn->real_escape_string(trim($_POST['mode_name']));
        $openingBalance = isset($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0.00;
        
        // Check if payment mode already exists
        $checkQuery = "SELECT id FROM payment_modes WHERE mode_name = '$modeName'";
        $checkResult = $conn->query($checkQuery);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Payment mode already exists']);
        } else {
            $query = "INSERT INTO payment_modes (mode_name, opening_balance) VALUES ('$modeName', $openingBalance)";
            
            if ($conn->query($query)) {
                log_action($conn, 'ADD', 'payment_modes', $conn->insert_id, "Added payment mode: $modeName with opening balance ₹" . number_format($openingBalance, 2));
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
