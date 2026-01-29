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
        $dateFilter = "WHERE date >= '$firstDay' AND date <= '$lastDay'";
        break;
        
    case 'last-month':
        $firstDay = date('Y-m-01', strtotime('-1 month'));
        $lastDay = date('Y-m-t', strtotime('-1 month'));
        $dateFilter = "WHERE date >= '$firstDay' AND date <= '$lastDay'";
        break;
        
    case 'this-year':
        $firstDay = date('Y-01-01');
        $lastDay = date('Y-12-31');
        $dateFilter = "WHERE date >= '$firstDay' AND date <= '$lastDay'";
        break;
        
    default: // 'all'
        $dateFilter = '';
        break;
}

$query = "SELECT * FROM incomes $dateFilter ORDER BY date DESC";
$result = $conn->query($query);

$incomes = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $incomes[] = $row;
    }
}

// Debug information (remove in production)
error_log("Filter: $filter, Query: $query, Count: " . count($incomes));

echo json_encode($incomes);
$conn->close();
?>