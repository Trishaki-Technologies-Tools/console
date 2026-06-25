<?php
header('Content-Type: application/json');
require_once 'config.php';

$query = "SELECT i.id, i.description, i.amount, i.date, i.created_at, i.attachment, 
                 COALESCE(c.category_name, 'Other') as category, 
                 COALESCE(p.mode_name, 'Cash') as payment_mode 
          FROM incomes i
          LEFT JOIN incomes_categories c ON i.category_id = c.id
          LEFT JOIN payment_modes p ON i.payment_mode_id = p.id
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
