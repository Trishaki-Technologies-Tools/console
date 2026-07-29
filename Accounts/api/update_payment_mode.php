<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && isset($_POST['opening_balance'])) {
        $id = intval($_POST['id']);
        $openingBalance = floatval($_POST['opening_balance']);
        
        // Fetch current payment mode details for logging
        $modeQuery = "SELECT mode_name, opening_balance FROM payment_modes WHERE id = $id";
        $modeResult = $conn->query($modeQuery);
        $modeRow = $modeResult ? $modeResult->fetch_assoc() : null;
        
        if ($modeRow) {
            $modeName = $modeRow['mode_name'];
            $oldBalance = floatval($modeRow['opening_balance']);
            
            $query = "UPDATE payment_modes SET opening_balance = $openingBalance WHERE id = $id";
            if ($conn->query($query)) {
                log_action($conn, 'EDIT', 'payment_modes', $id, "Updated opening balance of '$modeName' from ₹" . number_format($oldBalance, 2) . " to ₹" . number_format($openingBalance, 2));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Payment mode not found']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID and opening balance are required']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
