<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id']) && isset($_POST['opening_balance'])) {
        $id = intval($_POST['id']);
        $openingBalance = floatval($_POST['opening_balance']);
        
        // Fetch current payment mode details for logging
        $modeQuery = "SELECT mode_name, opening_balance, type, settlement_bank_id FROM payment_modes WHERE id = $id";
        $modeResult = $conn->query($modeQuery);
        $modeRow = $modeResult ? $modeResult->fetch_assoc() : null;
        
        if ($modeRow) {
            $modeName = $modeRow['mode_name'];
            $oldBalance = floatval($modeRow['opening_balance']);
            
            $typeSql = "type = COALESCE(type, 'bank')";
            $settlementBankIdSql = "settlement_bank_id = NULL";

            if (strtolower($modeName) === 'cash') {
                $typeSql = "type = NULL";
                $settlementBankIdSql = "settlement_bank_id = NULL";
            } else if (isset($_POST['type']) && in_array(strtolower($_POST['type']), ['bank', 'merchant'])) {
                $t = strtolower($_POST['type']);
                $typeSql = "type = '$t'";
                
                if ($t === 'merchant' && isset($_POST['settlement_bank_id']) && intval($_POST['settlement_bank_id']) > 0) {
                    $sbId = intval($_POST['settlement_bank_id']);
                    $sbCheck = $conn->query("SELECT id FROM payment_modes WHERE id = $sbId AND LOWER(type) = 'bank'");
                    if ($sbCheck && $sbCheck->num_rows > 0) {
                        $settlementBankIdSql = "settlement_bank_id = $sbId";
                    } else {
                        $settlementBankIdSql = "settlement_bank_id = NULL";
                    }
                } else {
                    $settlementBankIdSql = "settlement_bank_id = NULL";
                }
            }

            $query = "UPDATE payment_modes SET opening_balance = $openingBalance, $typeSql, $settlementBankIdSql WHERE id = $id";
            if ($conn->query($query)) {
                log_action($conn, 'EDIT', 'payment_modes', $id, "Updated payment mode '$modeName' (Balance: ₹" . number_format($openingBalance, 2) . ")");
                reconcileMerchantSettlements($conn);
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
