<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mode_name']) && trim($_POST['mode_name']) !== '') {
        $modeName = trim($_POST['mode_name']);
        $modeNameEscaped = $conn->real_escape_string($modeName);
        $openingBalance = isset($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0.00;
        
        ensurePaymentModesTablesExist($conn);
        
        // Check if payment mode already exists (case-insensitive check)
        $checkQuery = "SELECT id FROM payment_modes WHERE LOWER(TRIM(mode_name)) = LOWER(TRIM('$modeNameEscaped'))";
        $checkResult = $conn->query($checkQuery);
        
        if ($checkResult && $checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => "Payment mode '$modeName' already exists"]);
        } else {
            $type = isset($_POST['type']) ? trim($_POST['type']) : 'bank';
            $settlementBankIdSql = "NULL";

            if (strtolower($modeName) === 'cash') {
                $typeSql = "NULL";
            } else {
                $type = in_array(strtolower($type), ['bank', 'merchant']) ? strtolower($type) : 'bank';
                $typeSql = "'$type'";
                
                if ($type === 'merchant' && isset($_POST['settlement_bank_id']) && intval($_POST['settlement_bank_id']) > 0) {
                    $sbId = intval($_POST['settlement_bank_id']);
                    // Verify that selected settlement bank exists and is of type 'bank'
                    $sbCheck = $conn->query("SELECT id FROM payment_modes WHERE id = $sbId AND LOWER(type) = 'bank'");
                    if ($sbCheck && $sbCheck->num_rows > 0) {
                        $settlementBankIdSql = $sbId;
                    }
                }
            }

            $query = "INSERT INTO payment_modes (mode_name, opening_balance, type, settlement_bank_id) VALUES ('$modeNameEscaped', $openingBalance, $typeSql, $settlementBankIdSql)";
            
            if ($conn->query($query)) {
                $newId = $conn->insert_id;
                log_action($conn, 'ADD', 'payment_modes', $newId, "Added payment mode: $modeName with opening balance ₹" . number_format($openingBalance, 2));
                reconcileMerchantSettlements($conn);
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
