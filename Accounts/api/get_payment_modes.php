<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';

try {
    ensurePaymentModesTablesExist($conn);

    try {
        reconcileMerchantSettlements($conn);
    } catch (Throwable $tR) {
        // Log or ignore reconciliation error
    }

    $sql = "
        SELECT 
            pm.*,
            sb.mode_name AS settlement_bank_name
        FROM payment_modes pm
        LEFT JOIN payment_modes sb ON pm.settlement_bank_id = sb.id
        ORDER BY pm.id ASC
    ";
    $res = $conn->query($sql);
    $modes = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            try {
                $row['current_balance'] = calculatePaymentModeBalance($conn, $row['id']);
            } catch (Throwable $tB) {
                $row['current_balance'] = floatval($row['opening_balance'] ?? 0);
            }
            $modes[] = $row;
        }
    }
    echo json_encode($modes);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
if (isset($conn)) $conn->close();
?>
