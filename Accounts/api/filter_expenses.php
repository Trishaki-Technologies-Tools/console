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
        $dateFilter = "WHERE e.date >= '$firstDay' AND e.date <= '$lastDay'";
        break;
        
    case 'last-month':
        $firstDay = date('Y-m-01', strtotime('-1 month'));
        $lastDay = date('Y-m-t', strtotime('-1 month'));
        $dateFilter = "WHERE e.date >= '$firstDay' AND e.date <= '$lastDay'";
        break;
        
    case 'this-year':
        $firstDay = date('Y-01-01');
        $lastDay = date('Y-12-31');
        $dateFilter = "WHERE e.date >= '$firstDay' AND e.date <= '$lastDay'";
        break;
        
    default: // 'all'
        $dateFilter = '';
        break;
}

$query = "SELECT e.id, e.description, e.amount, e.date, e.created_at, e.attachment, 
                 COALESCE(c.category_name, 'Other') as category, 
                 COALESCE(p.mode_name, 'Cash') as payment_mode 
          FROM expenses e
          LEFT JOIN expenses_categories c ON e.category_id = c.id
          LEFT JOIN payment_modes p ON e.payment_mode_id = p.id
          $dateFilter 
          ORDER BY e.date DESC";
$result = $conn->query($query);

$expenses = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
}

echo json_encode($expenses);
$conn->close();
?>