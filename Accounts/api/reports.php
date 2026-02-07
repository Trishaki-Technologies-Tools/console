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
                SUM(CASE WHEN type = 'income' AND category != 'Loan' AND category != 'Opening Balance' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' AND category != 'Principal Amount' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'income' AND category = 'Loan' THEN amount ELSE 0 END) as loan_taken,
                SUM(CASE WHEN type = 'expense' AND category = 'Principal Amount' THEN amount ELSE 0 END) as loan_paid,
                MAX(CASE WHEN type = 'report' THEN opening_balance ELSE 0 END) as stored_opening
            FROM (
                SELECT date, amount, category, 'income' as type, 0 as opening_balance FROM incomes
                UNION ALL
                SELECT date, amount, category, 'expense' as type, 0 as opening_balance FROM expenses
                UNION ALL
                SELECT STR_TO_DATE(CONCAT('01-', month), '%d-%b-%Y') as date, 0 as amount, '' as category, 'report' as type, opening_balance FROM reports
            ) as combined
            GROUP BY y, m
            ORDER BY y ASC, m ASC
        ";
    } elseif ($type === 'quarterly') {
        // Quarterly/Yearly doesn't really have a 'stored opening' concept from monthly reports easily 
        // without complex logic. For now, we'll keep 0 or maybe try to find min month?
        // Let's stick to 0 for non-monthly to avoid complexity unless requested.
        $sql = "
            SELECT 
                CONCAT('Q', QUARTER(date), '-', YEAR(date)) as period_label,
                YEAR(date) as y,
                QUARTER(date) as q,
                SUM(CASE WHEN type = 'income' AND category != 'Loan' AND category != 'Opening Balance' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' AND category != 'Principal Amount' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'income' AND category = 'Loan' THEN amount ELSE 0 END) as loan_taken,
                SUM(CASE WHEN type = 'expense' AND category = 'Principal Amount' THEN amount ELSE 0 END) as loan_paid,
                0 as stored_opening
            FROM (
                SELECT date, amount, category, 'income' as type FROM incomes
                UNION ALL
                SELECT date, amount, category, 'expense' as type FROM expenses
            ) as combined
            GROUP BY y, q
            ORDER BY y ASC, q ASC
        ";
    } else { // Yearly
        $sql = "
            SELECT 
                CAST(YEAR(date) AS CHAR) as period_label,
                YEAR(date) as y,
                SUM(CASE WHEN type = 'income' AND category != 'Loan' AND category != 'Opening Balance' THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN type = 'expense' AND category != 'Principal Amount' THEN amount ELSE 0 END) as expenses,
                SUM(CASE WHEN type = 'income' AND category = 'Loan' THEN amount ELSE 0 END) as loan_taken,
                SUM(CASE WHEN type = 'expense' AND category = 'Principal Amount' THEN amount ELSE 0 END) as loan_paid,
                0 as stored_opening
            FROM (
                SELECT date, amount, category, 'income' as type FROM incomes
                UNION ALL
                SELECT date, amount, category, 'expense' as type FROM expenses
            ) as combined
            GROUP BY y
            ORDER BY y ASC
        ";
    }

    $result = $conn->query($sql);

    // 2. Calculate Opening/Closing Balances sequentially
    $runningBalance = null;
    
    // We need to process from oldest to newest to calculate balance, 
    // but typically users want to SEE newest first. 
    // So we calculate then reverse.
    
    $tempReports = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            
            // Initialize running balance with the stored opening of the very first record
            if ($runningBalance === null) {
                $runningBalance = floatval($row['stored_opening']);
            }

            $opening = $runningBalance;
            // Net calculates actual cash flow: (Income + Loans Taken) - (Expenses + Loans Paid)
            $net = (floatval($row['income']) + floatval($row['loan_taken'])) - (floatval($row['expenses']) + floatval($row['loan_paid']));
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
