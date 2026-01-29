<?php
require_once 'config.php';

$types = ['monthly', 'quarterly', 'yearly'];

foreach ($types as $type) {
    echo "Testing Type: $type\n";
    
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
    } else {
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
    if (!$result) {
        echo "Error: " . $conn->error . "\n";
    } else {
        echo "Rows: " . $result->num_rows . "\n";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
    }
    echo "-------------------\n";
}
$conn->close();
?>
