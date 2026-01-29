<?php
header('Content-Type: application/json');
require_once 'config.php';

$query = "SELECT * FROM expenses ORDER BY date DESC";
$result = $conn->query($query);

$expenses = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
}

echo json_encode($expenses);
$conn->close();
?>
