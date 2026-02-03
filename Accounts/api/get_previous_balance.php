<?php
error_reporting(0); // Suppress warnings
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_GET['month'])) {
    echo json_encode(['success' => false, 'error' => 'Month required']);
    exit;
}

$currentMonthStr = $_GET['month']; // YYYY-MM
// Force day to 01 to avoid overflow issues (e.g. 31st March - 1 month != Feb)
$dateObj = DateTime::createFromFormat('Y-m-d', $currentMonthStr . '-01');

if (!$dateObj) {
     echo json_encode(['success' => false, 'error' => 'Invalid date format']);
     exit;
}

$dateObj->modify('-1 month');
$prevMonth = strtoupper($dateObj->format('M-Y')); // e.g. FEB-2025

$sql = "SELECT closing_balance FROM reports WHERE month = '$prevMonth'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(['success' => true, 'opening_balance' => $row['closing_balance'], 'prev_month' => $prevMonth]);
} else {
    echo json_encode(['success' => false, 'opening_balance' => 0, 'message' => 'No previous report found']);
}

$conn->close();
?>
