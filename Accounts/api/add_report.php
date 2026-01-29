<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = $conn->real_escape_string($_POST['month']);
    $opening_balance = floatval($_POST['opening_balance']);
    $income = floatval($_POST['income']);
    $expenses = floatval($_POST['expenses']);
    $closing_balance = floatval($_POST['closing_balance']);
    
    $query = "INSERT INTO reports (month, opening_balance, income, expenses, closing_balance) 
              VALUES ('$month', $opening_balance, $income, $expenses, $closing_balance)";
    
    if ($conn->query($query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
