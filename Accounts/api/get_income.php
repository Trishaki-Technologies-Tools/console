<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = "SELECT * FROM incomes WHERE id = $id";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Income not found']);
    }
} else {
    echo json_encode(['error' => 'ID required']);
}
$conn->close();
?>
