<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch expense details first
    $detailsQuery = "SELECT description, amount, attachment FROM expenses WHERE id = $id";
    $detailsResult = $conn->query($detailsQuery);
    $expenseRow = $detailsResult ? $detailsResult->fetch_assoc() : null;
    $description = $expenseRow ? $expenseRow['description'] : 'Unknown';
    $amount = $expenseRow ? floatval($expenseRow['amount']) : 0;
    $filePath = $expenseRow ? $expenseRow['attachment'] : null;

    $query = "DELETE FROM expenses WHERE id = $id";
    
    if ($conn->query($query)) {
        if ($filePath && file_exists('../' . $filePath)) {
            unlink('../' . $filePath);
        }
        log_action($conn, 'DELETE', 'expenses', $id, "Deleted expense: $description (₹$amount)");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
