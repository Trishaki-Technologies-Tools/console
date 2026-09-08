<?php
// api/settlement_helper.php

if (!function_exists('reconcileMerchantSettlements')) {
    /**
     * Automatically calculates/reconciles T+1 daily net settlements for merchant payment modes.
     * Net daily settlement = SUM(Credits/Incomes) - SUM(Debits/Expenses) for transaction date D.
     * T+1 Settlement Date = D + 1 day.
     */
    function reconcileMerchantSettlements($conn, $merchant_mode_id = null, $target_date = null) {
        $whereMode = $merchant_mode_id ? "AND p.id = " . intval($merchant_mode_id) : "";
        $modesQuery = "SELECT p.id AS merchant_mode_id, p.settlement_bank_id 
                       FROM payment_modes p 
                       WHERE LOWER(p.type) = 'merchant' AND p.settlement_bank_id IS NOT NULL AND p.settlement_bank_id > 0 $whereMode";
        $modesRes = $conn->query($modesQuery);
        if (!$modesRes) return;
        
        while ($m = $modesRes->fetch_assoc()) {
            $mId = intval($m['merchant_mode_id']);
            $bId = intval($m['settlement_bank_id']);
            
            $whereDateInc = $target_date ? "AND date = '" . $conn->real_escape_string($target_date) . "'" : "";
            $whereDateExp = $target_date ? "AND date = '" . $conn->real_escape_string($target_date) . "'" : "";
            
            $datesQuery = "
                SELECT txn_date FROM (
                    SELECT date AS txn_date FROM incomes WHERE payment_mode_id = $mId $whereDateInc
                    UNION
                    SELECT date AS txn_date FROM expenses WHERE payment_mode_id = $mId $whereDateExp
                ) AS dates GROUP BY txn_date
            ";
            $datesRes = $conn->query($datesQuery);
            if (!$datesRes) continue;
            
            while ($dRow = $datesRes->fetch_assoc()) {
                $txnDate = $dRow['txn_date'];
                if (!$txnDate) continue;
                
                // Total credits (incomes) for this date & mode
                $credits = 0.00;
                $cRes = $conn->query("SELECT SUM(amount) AS total FROM incomes WHERE payment_mode_id = $mId AND date = '$txnDate'");
                if ($cRes && $cRow = $cRes->fetch_assoc()) {
                    $credits = floatval($cRow['total'] ?? 0);
                }
                
                // Total debits (expenses) for this date & mode
                $debits = 0.00;
                $dRes = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE payment_mode_id = $mId AND date = '$txnDate'");
                if ($dRes && $dRow2 = $dRes->fetch_assoc()) {
                    $debits = floatval($dRow2['total'] ?? 0);
                }
                
                $netAmount = max(0.00, $credits - $debits);
                
                // T+1 Settlement Date = txnDate + 1 day
                $settlementDate = date('Y-m-d', strtotime($txnDate . ' +1 day'));
                
                // Check if settlement record already exists
                $checkStmt = $conn->query("SELECT id FROM merchant_settlements WHERE merchant_mode_id = $mId AND settlement_date = '$settlementDate'");
                if ($checkStmt && $checkRow = $checkStmt->fetch_assoc()) {
                    $settlementId = $checkRow['id'];
                    $conn->query("UPDATE merchant_settlements SET bank_mode_id = $bId, amount = $netAmount WHERE id = $settlementId");
                } else {
                    if ($netAmount > 0) {
                        $conn->query("INSERT INTO merchant_settlements (merchant_mode_id, bank_mode_id, settlement_date, amount) VALUES ($mId, $bId, '$settlementDate', $netAmount)");
                    }
                }
            }
        }
    }
}

if (!function_exists('calculatePaymentModeBalance')) {
    /**
     * Calculates the real-time balance for a given payment mode considering:
     * - Opening balance
     * - Direct incomes (+)
     * - Direct expenses (-)
     * - T+1 Settlement inflows (+) for Banks
     * - T+1 Settlement outflows (-) for Merchants
     */
    function calculatePaymentModeBalance($conn, $modeId, $asOfDate = null) {
        $modeId = intval($modeId);
        $asOfDate = $asOfDate ? $conn->real_escape_string($asOfDate) : date('Y-m-d');
        
        $modeRes = $conn->query("SELECT type, opening_balance FROM payment_modes WHERE id = $modeId");
        if (!$modeRes || !($m = $modeRes->fetch_assoc())) return 0.00;
        
        $type = strtolower($m['type'] ?? '');
        $opening = floatval($m['opening_balance'] ?? 0);
        
        // Incomes up to asOfDate
        $incRes = $conn->query("SELECT SUM(amount) AS total FROM incomes WHERE payment_mode_id = $modeId AND date <= '$asOfDate'");
        $inc = ($incRes && $r = $incRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00;
        
        // Expenses up to asOfDate
        $expRes = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE payment_mode_id = $modeId AND date <= '$asOfDate'");
        $exp = ($expRes && $r = $expRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00;
        
        $settlementAdjustment = 0.00;
        if ($type === 'bank') {
            // Settlements transferred IN to this bank account on or before asOfDate
            $settleInRes = $conn->query("SELECT SUM(amount) AS total FROM merchant_settlements WHERE bank_mode_id = $modeId AND settlement_date <= '$asOfDate'");
            $settlementAdjustment = ($settleInRes && $r = $settleInRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00;
        } else if ($type === 'merchant') {
            // Settlements transferred OUT of this merchant account to bank on or before asOfDate
            $settleOutRes = $conn->query("SELECT SUM(amount) AS total FROM merchant_settlements WHERE merchant_mode_id = $modeId AND settlement_date <= '$asOfDate'");
            $settlementAdjustment = -1 * (($settleOutRes && $r = $settleOutRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00);
        }
        
        return $opening + $inc - $exp + $settlementAdjustment;
    }
}
?>
