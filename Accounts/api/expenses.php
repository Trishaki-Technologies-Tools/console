<?php
header('Content-Type: application/json');
require_once 'config.php';

$query = "SELECT e.id, e.description, e.amount, e.date, e.created_at, e.attachment, 
                 COALESCE(c.category_name, 'Other') as category, 
                 COALESCE(p.mode_name, 'Cash') as payment_mode 
          FROM expenses e
          LEFT JOIN expenses_categories c ON e.category_id = c.id
          LEFT JOIN payment_modes p ON e.payment_mode_id = p.id
          ORDER BY e.date DESC";
$result = $conn->query($query);

$expenses = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $row['token'] = encryptToken("EXP-" . $row['id']);
        $expenses[] = $row;
    }
}

echo json_encode($expenses);
$conn->close();
?>
