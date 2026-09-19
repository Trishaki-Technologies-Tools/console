<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';

try {
    reconcileMerchantSettlements($conn);
} catch (Throwable $e) {}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$dateFilterExp = '';
$dateFilterSettle = '';

switch ($filter) {
    case 'this-month':
        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');
        $dateFilterExp = "WHERE e.date >= '$firstDay' AND e.date <= '$lastDay'";
        $dateFilterSettle = "AND s.settlement_date >= '$firstDay' AND s.settlement_date <= '$lastDay'";
        break;
        
    case 'last-month':
        $firstDay = date('Y-m-01', strtotime('-1 month'));
        $lastDay = date('Y-m-t', strtotime('-1 month'));
        $dateFilterExp = "WHERE e.date >= '$firstDay' AND e.date <= '$lastDay'";
        $dateFilterSettle = "AND s.settlement_date >= '$firstDay' AND s.settlement_date <= '$lastDay'";
        break;
        
    case 'this-year':
        $firstDay = date('Y-01-01');
        $lastDay = date('Y-12-31');
        $dateFilterExp = "WHERE e.date >= '$firstDay' AND e.date <= '$lastDay'";
        $dateFilterSettle = "AND s.settlement_date >= '$firstDay' AND s.settlement_date <= '$lastDay'";
        break;
        
    default:
        break;
}

$query = "SELECT e.id, e.description, e.amount, e.date, e.created_at, e.attachment, 
                 COALESCE(c.category_name, 'Other') as category, 
                 COALESCE(p.mode_name, 'Cash') as payment_mode,
                 0 as is_settlement
          FROM expenses e
          LEFT JOIN expenses_categories c ON e.category_id = c.id
          LEFT JOIN payment_modes p ON e.payment_mode_id = p.id
          $dateFilterExp 
          ORDER BY e.date DESC";
$result = $conn->query($query);

$expenses = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
}

// Append settlement debit entries
$sQuery = "
    SELECT 
        CONCAT('settle_', s.id) AS id,
        CONCAT('Auto Settlement to ', b.mode_name) AS description,
        s.amount,
        s.settlement_date AS date,
        COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) AS created_at,
        'Not Added' AS attachment,
        'Settlement' AS category,
        m.mode_name AS payment_mode,
        1 as is_settlement
    FROM merchant_settlements s
    JOIN payment_modes m ON s.merchant_mode_id = m.id
    JOIN payment_modes b ON s.bank_mode_id = b.id
    WHERE COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) <= NOW()
      AND s.amount > 0
      $dateFilterSettle
";
$sRes = $conn->query($sQuery);
if ($sRes && $sRes->num_rows > 0) {
    while($sRow = $sRes->fetch_assoc()) {
        $expenses[] = $sRow;
    }
}

usort($expenses, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

echo json_encode($expenses);
if (isset($conn)) $conn->close();
?>