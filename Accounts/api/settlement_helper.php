<?php
// api/settlement_helper.php

if (!function_exists('ensurePaymentModesTablesExist')) {
    function ensurePaymentModesTablesExist($conn) {
        if (!$conn) return;
        try {
            $conn->query("
                CREATE TABLE IF NOT EXISTS `payment_modes` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `mode_name` VARCHAR(100) NOT NULL UNIQUE,
                    `opening_balance` DECIMAL(15,2) DEFAULT 0.00,
                    `type` VARCHAR(50) DEFAULT NULL,
                    `settlement_bank_id` INT DEFAULT NULL,
                    `status` VARCHAR(20) DEFAULT 'active',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Ensure columns exist in payment_modes if created earlier with older schema
            $colRes = $conn->query("SHOW COLUMNS FROM `payment_modes` LIKE 'type'");
            if ($colRes && $colRes->num_rows === 0) {
                $conn->query("ALTER TABLE `payment_modes` ADD COLUMN `type` VARCHAR(50) DEFAULT NULL AFTER `opening_balance`");
            }
            $colRes2 = $conn->query("SHOW COLUMNS FROM `payment_modes` LIKE 'settlement_bank_id'");
            if ($colRes2 && $colRes2->num_rows === 0) {
                $conn->query("ALTER TABLE `payment_modes` ADD COLUMN `settlement_bank_id` INT DEFAULT NULL AFTER `type`");
            }

            $conn->query("
                CREATE TABLE IF NOT EXISTS `merchant_settlements` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `merchant_mode_id` INT NOT NULL,
                    `bank_mode_id` INT NOT NULL,
                    `txn_date` DATE DEFAULT NULL,
                    `settlement_date` DATE NOT NULL,
                    `settlement_time` TIME DEFAULT '05:30:00',
                    `settlement_datetime` DATETIME DEFAULT NULL,
                    `amount` DECIMAL(15,2) DEFAULT 0.00,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $colRes3 = $conn->query("SHOW COLUMNS FROM `merchant_settlements` LIKE 'txn_date'");
            if ($colRes3 && $colRes3->num_rows === 0) {
                $conn->query("ALTER TABLE `merchant_settlements` ADD COLUMN `txn_date` DATE DEFAULT NULL AFTER `bank_mode_id`");
            }

            $colRes4 = $conn->query("SHOW COLUMNS FROM `merchant_settlements` LIKE 'settlement_datetime'");
            if ($colRes4 && $colRes4->num_rows === 0) {
                $conn->query("ALTER TABLE `merchant_settlements` ADD COLUMN `settlement_datetime` DATETIME DEFAULT NULL AFTER `settlement_date`");
            }
        } catch (Throwable $e) {
            // Silence table creation error if user lacks DDL permissions
        }
    }
}

if (!function_exists('getTablePaymentModeWhere')) {
    /**
     * Safely constructs WHERE clause for payment mode matching depending on existing table schema.
     */
    function getTablePaymentModeWhere($conn, $table, $mId, $mName) {
        static $colsCache = [];
        if (!isset($colsCache[$table])) {
            $colsCache[$table] = [];
            $res = $conn->query("SHOW COLUMNS FROM `$table`");
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $colsCache[$table][] = strtolower($r['Field']);
                }
            }
        }

        $cols = $colsCache[$table];
        $mNameEsc = $conn->real_escape_string($mName);
        $mId = intval($mId);

        $conditions = [];
        if (in_array('payment_mode_id', $cols)) {
            $conditions[] = "payment_mode_id = $mId";
        }
        if (in_array('payment_mode', $cols)) {
            $conditions[] = "LOWER(TRIM(payment_mode)) = LOWER('$mNameEsc')";
        }

        if (empty($conditions)) {
            return "1=0";
        }
        return "(" . implode(" OR ", $conditions) . ")";
    }
}

if (!function_exists('getReceiptCreditsForMode')) {
    /**
     * Calculates credits from receipts for a specific payment mode.
     * If $targetDate is provided, returns total amount for that date.
     * If $targetDate is null, returns an associative array of [ 'YYYY-MM-DD' => total_amount ].
     */
    function getReceiptCreditsForMode($conn, $modeName, $targetDate = null, $asOfDate = null) {
        if (!$conn || empty($modeName)) return $targetDate ? 0.00 : [];
        $mNameEsc = $conn->real_escape_string($modeName);
        
        $sql = "SELECT id, receipt_date, items, cumulative_total_paid FROM receipts WHERE items LIKE '%$mNameEsc%'";
        if ($targetDate) {
            $tDateEsc = $conn->real_escape_string($targetDate);
            $sql .= " AND (receipt_date = '$tDateEsc' OR items LIKE '%$tDateEsc%')";
        }
        
        $res = $conn->query($sql);
        if (!$res) return $targetDate ? 0.00 : [];
        
        $dateTotals = [];
        $singleTotal = 0.00;
        
        while ($row = $res->fetch_assoc()) {
            $items = json_decode($row['items'] ?? '[]', true);
            if (!is_array($items) || empty($items)) {
                continue;
            }
            
            foreach ($items as $itm) {
                $rawMode = trim($itm['paymentMode'] ?? $itm['payment_mode'] ?? $itm['mode'] ?? '');
                if (strcasecmp($rawMode, $modeName) !== 0) {
                    continue;
                }
                
                $itmDate = !empty($itm['date']) ? $itm['date'] : $row['receipt_date'];
                if (!$itmDate) continue;
                
                $amt = floatval($itm['paidAmt'] ?? $itm['paid_amount'] ?? $itm['amount'] ?? $itm['totalInclTax'] ?? 0);
                if ($amt <= 0) continue;
                
                if ($targetDate) {
                    if ($itmDate === $targetDate) {
                        $singleTotal += $amt;
                    }
                } else {
                    if ($asOfDate && $itmDate > $asOfDate) {
                        continue;
                    }
                    if (!isset($dateTotals[$itmDate])) {
                        $dateTotals[$itmDate] = 0.00;
                    }
                    $dateTotals[$itmDate] += $amt;
                }
            }
        }
        
        return $targetDate ? $singleTotal : $dateTotals;
    }
}

if (!function_exists('reconcileMerchantSettlements')) {
    /**
     * Automatically calculates/reconciles T+1 daily net settlements for merchant payment modes.
     * Net daily settlement = SUM(Credits: Incomes + Receipts) - SUM(Debits: Expenses) for transaction date D.
     * T+1 Settlement Date = D + 1 day @ 05:30 AM.
     */
    function reconcileMerchantSettlements($conn, $merchant_mode_id = null, $target_date = null) {
        if (!$conn) return;
        ensurePaymentModesTablesExist($conn);

        try {
            $whereMode = $merchant_mode_id ? "AND p.id = " . intval($merchant_mode_id) : "";
            // Fetch merchant modes or modes configured with a settlement bank or designated as merchant
            $modesQuery = "SELECT p.id AS merchant_mode_id, p.mode_name, p.settlement_bank_id, p.type 
                           FROM payment_modes p 
                           WHERE (LOWER(p.type) = 'merchant' OR (p.settlement_bank_id IS NOT NULL AND p.settlement_bank_id > 0)) $whereMode";
            $modesRes = $conn->query($modesQuery);
            if (!$modesRes) return;
            
            while ($m = $modesRes->fetch_assoc()) {
                $mId = intval($m['merchant_mode_id']);
                $mName = trim($m['mode_name']);
                $bId = intval($m['settlement_bank_id']);
                
                // Fallback to first available Bank payment mode if no settlement bank ID was explicitly linked
                if ($bId <= 0) {
                    $bankRes = $conn->query("SELECT id FROM payment_modes WHERE LOWER(type) = 'bank' AND id != $mId ORDER BY id ASC LIMIT 1");
                    if ($bankRes && $bRow = $bankRes->fetch_assoc()) {
                        $bId = intval($bRow['id']);
                    } else {
                        $bankRes2 = $conn->query("SELECT id FROM payment_modes WHERE id != $mId ORDER BY id ASC LIMIT 1");
                        if ($bankRes2 && $bRow2 = $bankRes2->fetch_assoc()) {
                            $bId = intval($bRow2['id']);
                        }
                    }
                }
                if ($bId <= 0) continue; // Cannot settle without a bank mode
                
                $incWhere = getTablePaymentModeWhere($conn, 'incomes', $mId, $mName);
                $expWhere = getTablePaymentModeWhere($conn, 'expenses', $mId, $mName);
                
                $whereDateInc = $target_date ? "AND date = '" . $conn->real_escape_string($target_date) . "'" : "";
                $whereDateExp = $target_date ? "AND date = '" . $conn->real_escape_string($target_date) . "'" : "";
                
                // Fetch all dates from receipts for this mode
                $receiptDatesMap = getReceiptCreditsForMode($conn, $mName);
                
                // Include dates from incomes, expenses, existing merchant_settlements
                $datesQuery = "
                    SELECT txn_date FROM (
                        SELECT date AS txn_date FROM incomes WHERE $incWhere $whereDateInc
                        UNION
                        SELECT date AS txn_date FROM expenses WHERE $expWhere $whereDateExp
                        UNION
                        SELECT COALESCE(txn_date, DATE_SUB(settlement_date, INTERVAL 1 DAY)) AS txn_date FROM merchant_settlements WHERE merchant_mode_id = $mId
                    ) AS dates GROUP BY txn_date
                ";
                $datesRes = $conn->query($datesQuery);
                $allDates = [];
                if ($datesRes) {
                    while ($dRow = $datesRes->fetch_assoc()) {
                        if (!empty($dRow['txn_date'])) {
                            $allDates[$dRow['txn_date']] = true;
                        }
                    }
                }
                
                // Merge in receipt dates
                foreach ($receiptDatesMap as $rDate => $rAmt) {
                    if (!empty($rDate)) {
                        if ($target_date && $rDate !== $target_date) continue;
                        $allDates[$rDate] = true;
                    }
                }
                
                foreach (array_keys($allDates) as $txnDate) {
                    if (!$txnDate) continue;
                    if ($target_date && $txnDate !== $target_date) continue;
                    
                    // Direct incomes
                    $directInc = 0.00;
                    $cRes = $conn->query("SELECT SUM(amount) AS total FROM incomes WHERE $incWhere AND date = '$txnDate'");
                    if ($cRes && $cRow = $cRes->fetch_assoc()) {
                        $directInc = floatval($cRow['total'] ?? 0);
                    }
                    
                    // Receipt credits
                    $receiptInc = $receiptDatesMap[$txnDate] ?? getReceiptCreditsForMode($conn, $mName, $txnDate);
                    
                    // Total credits
                    $credits = $directInc + floatval($receiptInc);
                    
                    // Total debits (expenses) for this date & mode
                    $debits = 0.00;
                    $dRes = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE $expWhere AND date = '$txnDate'");
                    if ($dRes && $dRow2 = $dRes->fetch_assoc()) {
                        $debits = floatval($dRow2['total'] ?? 0);
                    }
                    
                    // Net daily settlement amount (accounting for debits)
                    $netAmount = max(0.00, $credits - $debits);
                    
                    // T+1 Settlement Date = txnDate + 1 day at 05:30:00 AM
                    $settlementDate = date('Y-m-d', strtotime($txnDate . ' +1 day'));
                    $settlementDateTime = $settlementDate . ' 05:30:00';
                    
                    // Check if settlement record already exists
                    $checkStmt = $conn->query("SELECT id FROM merchant_settlements WHERE merchant_mode_id = $mId AND (txn_date = '$txnDate' OR settlement_date = '$settlementDate')");
                    if ($checkStmt && $checkRow = $checkStmt->fetch_assoc()) {
                        $settlementId = $checkRow['id'];
                        if ($netAmount > 0) {
                            $conn->query("UPDATE merchant_settlements 
                                          SET bank_mode_id = $bId, 
                                              txn_date = '$txnDate', 
                                              settlement_date = '$settlementDate', 
                                              settlement_datetime = '$settlementDateTime', 
                                              amount = $netAmount 
                                          WHERE id = $settlementId");
                        } else {
                            $conn->query("DELETE FROM merchant_settlements WHERE id = $settlementId");
                        }
                    } else {
                        if ($netAmount > 0) {
                            $conn->query("INSERT INTO merchant_settlements 
                                          (merchant_mode_id, bank_mode_id, txn_date, settlement_date, settlement_datetime, amount) 
                                          VALUES ($mId, $bId, '$txnDate', '$settlementDate', '$settlementDateTime', $netAmount)");
                        }
                    }
                }
                
                // Clean up any remaining settlement records with zero or negative amounts
                $conn->query("DELETE FROM merchant_settlements WHERE merchant_mode_id = $mId AND amount <= 0");
            }

            syncSystemCategories($conn);
        } catch (Throwable $e) {
            // Silence settlement calculation errors
        }
    }
}

if (!function_exists('syncSystemCategories')) {
    /**
     * Auto-adds or auto-removes system categories (Sales, Settlement) based on data existence.
     */
    function syncSystemCategories($conn) {
        if (!$conn) return;
        try {
            // Clean up any legacy 'Merchant Settlement' category
            $conn->query("DELETE FROM incomes_categories WHERE LOWER(TRIM(category_name)) = 'merchant settlement'");
            $conn->query("DELETE FROM expenses_categories WHERE LOWER(TRIM(category_name)) = 'merchant settlement'");

            // 1. Check invoices/receipts count
            $invCount = 0;
            $recCount = 0;
            $resI = $conn->query("SELECT COUNT(*) as cnt FROM invoices");
            if ($resI && $rI = $resI->fetch_assoc()) $invCount = intval($rI['cnt']);
            $resR = $conn->query("SELECT COUNT(*) as cnt FROM receipts");
            if ($resR && $rR = $resR->fetch_assoc()) $recCount = intval($rR['cnt']);
            
            $hasSales = ($invCount > 0 || $recCount > 0);
            
            // Check Sales in incomes_categories
            $salesRes = $conn->query("SELECT id FROM incomes_categories WHERE LOWER(TRIM(category_name)) = 'sales'");
            if ($hasSales) {
                if ($salesRes && $salesRes->num_rows === 0) {
                    $conn->query("INSERT INTO incomes_categories (category_name) VALUES ('Sales')");
                }
            } else {
                if ($salesRes && $salesRow = $salesRes->fetch_assoc()) {
                    $salesId = $salesRow['id'];
                    $used = $conn->query("SELECT COUNT(*) as cnt FROM incomes WHERE category_id = $salesId");
                    $usedCnt = ($used && $uRow = $used->fetch_assoc()) ? intval($uRow['cnt']) : 0;
                    if ($usedCnt === 0) {
                        $conn->query("DELETE FROM incomes_categories WHERE id = $salesId");
                    }
                }
            }

            // 2. Check settlements count
            $settleCount = 0;
            $resS = $conn->query("SELECT COUNT(*) as cnt FROM merchant_settlements WHERE amount > 0");
            if ($resS && $rS = $resS->fetch_assoc()) $settleCount = intval($rS['cnt']);
            $hasSettlements = ($settleCount > 0);

            // Settlement in incomes_categories
            $settleIncRes = $conn->query("SELECT id FROM incomes_categories WHERE LOWER(TRIM(category_name)) = 'settlement'");
            if ($hasSettlements) {
                if ($settleIncRes && $settleIncRes->num_rows === 0) {
                    $conn->query("INSERT INTO incomes_categories (category_name) VALUES ('Settlement')");
                }
            } else {
                if ($settleIncRes && $settleIncRow = $settleIncRes->fetch_assoc()) {
                    $sIncId = $settleIncRow['id'];
                    $used = $conn->query("SELECT COUNT(*) as cnt FROM incomes WHERE category_id = $sIncId");
                    $usedCnt = ($used && $uRow = $used->fetch_assoc()) ? intval($uRow['cnt']) : 0;
                    if ($usedCnt === 0) {
                        $conn->query("DELETE FROM incomes_categories WHERE id = $sIncId");
                    }
                }
            }

            // Settlement in expenses_categories
            $settleExpRes = $conn->query("SELECT id FROM expenses_categories WHERE LOWER(TRIM(category_name)) = 'settlement'");
            if ($hasSettlements) {
                if ($settleExpRes && $settleExpRes->num_rows === 0) {
                    $conn->query("INSERT INTO expenses_categories (category_name) VALUES ('Settlement')");
                }
            } else {
                if ($settleExpRes && $settleExpRow = $settleExpRes->fetch_assoc()) {
                    $sExpId = $settleExpRow['id'];
                    $used = $conn->query("SELECT COUNT(*) as cnt FROM expenses WHERE category_id = $sExpId");
                    $usedCnt = ($used && $uRow = $used->fetch_assoc()) ? intval($uRow['cnt']) : 0;
                    if ($usedCnt === 0) {
                        $conn->query("DELETE FROM expenses_categories WHERE id = $sExpId");
                    }
                }
            }
        } catch (Throwable $e) {}
    }
}

if (!function_exists('calculatePaymentModeBalance')) {
    /**
     * Calculates the real-time balance for a given payment mode considering:
     * - Opening balance
     * - Direct incomes (+)
     * - Payment receipts (+)
     * - Direct expenses (-)
     * - T+1 @ 05:30 AM Settlement inflows (+) for Banks
     * - T+1 @ 05:30 AM Settlement outflows (-) for Merchants
     */
    function calculatePaymentModeBalance($conn, $modeId, $asOfDateTime = null) {
        if (!$conn) return 0.00;
        $modeId = intval($modeId);
        
        if (!$asOfDateTime) {
            $asOfDateTime = date('Y-m-d H:i:s');
        } else if (strlen($asOfDateTime) === 10) {
            $asOfDateTime .= ' 23:59:59';
        }
        $asOfDateTimeEsc = $conn->real_escape_string($asOfDateTime);
        $asOfDateOnly = substr($asOfDateTime, 0, 10);
        
        try {
            $modeRes = $conn->query("SELECT mode_name, type, opening_balance FROM payment_modes WHERE id = $modeId");
            if (!$modeRes || !($m = $modeRes->fetch_assoc())) return 0.00;
            
            $modeName = trim($m['mode_name']);
            $type = strtolower($m['type'] ?? '');
            $opening = floatval($m['opening_balance'] ?? 0);
            
            $incWhere = getTablePaymentModeWhere($conn, 'incomes', $modeId, $modeName);
            $expWhere = getTablePaymentModeWhere($conn, 'expenses', $modeId, $modeName);
            
            // Incomes up to asOfDate
            $incRes = $conn->query("SELECT SUM(amount) AS total FROM incomes WHERE $incWhere AND date <= '$asOfDateOnly'");
            $inc = ($incRes && $r = $incRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00;
            
            // Receipts credits up to asOfDate
            $recMap = getReceiptCreditsForMode($conn, $modeName, null, $asOfDateOnly);
            $recCredits = is_array($recMap) ? array_sum($recMap) : floatval($recMap);
            
            // Expenses up to asOfDate
            $expRes = $conn->query("SELECT SUM(amount) AS total FROM expenses WHERE $expWhere AND date <= '$asOfDateOnly'");
            $exp = ($expRes && $r = $expRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00;
            
            $settlementAdjustment = 0.00;
            if ($type === 'bank') {
                // Settlements transferred IN to this bank account on or before asOfDateTime (must have reached 05:30 AM T+1)
                $settleInRes = $conn->query("
                    SELECT SUM(amount) AS total FROM merchant_settlements 
                    WHERE bank_mode_id = $modeId 
                      AND COALESCE(settlement_datetime, CONCAT(settlement_date, ' 05:30:00')) <= '$asOfDateTimeEsc'
                ");
                $settlementAdjustment = ($settleInRes && $r = $settleInRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00;
            } else if ($type === 'merchant') {
                // Settlements transferred OUT of this merchant account to bank on or before asOfDateTime (must have reached 05:30 AM T+1)
                $settleOutRes = $conn->query("
                    SELECT SUM(amount) AS total FROM merchant_settlements 
                    WHERE merchant_mode_id = $modeId 
                      AND COALESCE(settlement_datetime, CONCAT(settlement_date, ' 05:30:00')) <= '$asOfDateTimeEsc'
                ");
                $settlementAdjustment = -1 * (($settleOutRes && $r = $settleOutRes->fetch_assoc()) ? floatval($r['total'] ?? 0) : 0.00);
            }
            
            return $opening + $inc + $recCredits - $exp + $settlementAdjustment;
        } catch (Throwable $e) {
            return 0.00;
        }
    }
}
?>
