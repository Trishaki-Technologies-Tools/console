<?php
header('Content-Type: application/json');
require_once 'config.php';

$sql = "SELECT * FROM employees WHERE status = 'Active' ORDER BY name ASC";
$result = $conn->query($sql);

$employees = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

echo json_encode($employees);
$conn->close();
?>
