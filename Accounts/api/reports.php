<?php
header('Content-Type: application/json');
require_once 'config.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'monthly';

$reports = [];

try {
    // 1. Fetch all raw transaction data grouped by period
    if ($type === 'monthly') {
        $sql = "
            SELECT 
                DATE_FORMAT(date, '%b-%Y') as period_label,
                YEAR(date) as y,
                MONTH(date) as m,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            FROM (
                SELECT date, amount, 'income' as type FROM incomes
                UNION ALL
                SELECT date, amount, 'expense' as type FROM expenses
            ) as combined
            GROUP BY y, m
            ORDER BY y ASC, m ASC
        ";
    } elseif ($type === 'quarterly') {
        $sql = "
            SELECT 
                CONCAT('Q', QUARTER(date), '-', YEAR(date)) as period_label,
                YEAR(date) as y,
                QUARTER(date) as q,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            FROM (
                SELECT date, amount, 'income' as type FROM incomes
                UNION ALL
                SELECT date, amount, 'expense' as type FROM expenses
            ) as combined
            GROUP BY y, q
            ORDER BY y ASC, q ASC
        ";
    } else { // Yearly
        $sql = "
            SELECT 
                CAST(YEAR(date) AS CHAR) as period_label,
                YEAR(date) as y,
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses
            FROM (
                SELECT date, amount, 'income' as type FROM incomes
                UNION ALL
                SELECT date, amount, 'expense' as type FROM expenses
            ) as combined
            GROUP BY y
            ORDER BY y ASC
        ";
    }

    $result = $conn->query($sql);

    // 2. Calculate Opening/Closing Balances sequentially
    // Assuming cumulative balance starts at 0 from the beginning of recorded history.
    $runningBalance = 0;
    
    // We need to process from oldest to newest to calculate balance, 
    // but typically users want to SEE newest first. 
    // So we calculate then reverse.
    
    $tempReports = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $opening = $runningBalance;
            $net = floatval($row['income']) - floatval($row['expenses']);
            $closing = $opening + $net;
            
            $row['period'] = strtoupper($row['period_label']);
            $row['opening_balance'] = $opening;
            $row['closing_balance'] = $closing;
            
            // Identify current period
            if ($type === 'monthly') {
                 $isCurrent = (strtoupper(date('M-Y')) === $row['period']) ? 1 : 0;
            } elseif ($type === 'quarterly') {
                 $curQ = 'Q' . ceil(date('n')/3) . '-' . date('Y');
                 $isCurrent = ($curQ === $row['period']) ? 1 : 0;
            } else {
                 $isCurrent = (date('Y') === $row['period']) ? 1 : 0;
            }
            $row['is_current_period'] = $isCurrent;
            
            $tempReports[] = $row;
            
            $runningBalance = $closing;
        }
    }

    // 3. Return latest first
    $reports = array_reverse($tempReports);

    echo json_encode($reports);

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
