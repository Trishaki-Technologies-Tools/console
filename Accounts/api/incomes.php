<?php
header('Content-Type: application/json');
require_once 'config.php';

$query = "SELECT * FROM incomes ORDER BY date DESC";
$result = $conn->query($query);

$incomes = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $incomes[] = $row;
    }
}

echo json_encode($incomes);
$conn->close();
?>
