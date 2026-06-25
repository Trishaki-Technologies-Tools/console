<?php
header('Content-Type: application/json');
require_once 'config.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build date filter based on selection
$dateFilter = '';

switch ($filter) {
    case 'this-month':
        $firstDay = date('Y-m-01');
        $lastDay = date('Y-m-t');
        $dateFilter = "WHERE i.date >= '$firstDay' AND i.date <= '$lastDay'";
        break;
        
    case 'last-month':
        $firstDay = date('Y-m-01', strtotime('-1 month'));
        $lastDay = date('Y-m-t', strtotime('-1 month'));
        $dateFilter = "WHERE i.date >= '$firstDay' AND i.date <= '$lastDay'";
        break;
        
    case 'this-year':
        $firstDay = date('Y-01-01');
        $lastDay = date('Y-12-31');
        $dateFilter = "WHERE i.date >= '$firstDay' AND i.date <= '$lastDay'";
        break;
        
    default: // 'all'
        $dateFilter = '';
        break;
}

$query = "SELECT i.id, i.description, i.amount, i.date, i.created_at, i.attachment, 
                 COALESCE(c.category_name, 'Other') as category, 
                 COALESCE(p.mode_name, 'Cash') as payment_mode 
          FROM incomes i
          LEFT JOIN incomes_categories c ON i.category_id = c.id
          LEFT JOIN payment_modes p ON i.payment_mode_id = p.id
          $dateFilter 
          ORDER BY i.date DESC";
$result = $conn->query($query);

$incomes = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $incomes[] = $row;
    }
}

echo json_encode($incomes);
$conn->close();
?>