<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get current month in IST
$currentMonth = strtoupper(date('M-Y'));

// Check if report exists for current month
$checkQuery = "SELECT id FROM reports WHERE month = '$currentMonth'";
$result = $conn->query($checkQuery);

if ($result->num_rows > 0) {
    echo json_encode(['exists' => true, 'month' => $currentMonth]);
} else {
    echo json_encode(['exists' => false, 'month' => $currentMonth]);
}

$conn->close();
?>
