<?php
header('Content-Type: application/json');
require_once 'config.php';

$query = "SELECT * FROM expenses_categories ORDER BY id ASC";
$result = $conn->query($query);

$categories = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

echo json_encode($categories);
$conn->close();
?>
