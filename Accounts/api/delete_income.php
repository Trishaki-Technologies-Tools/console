<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch income details first
    $detailsQuery = "SELECT description, amount, attachment FROM incomes WHERE id = $id";
    $detailsResult = $conn->query($detailsQuery);
    $incomeRow = $detailsResult ? $detailsResult->fetch_assoc() : null;
    $description = $incomeRow ? $incomeRow['description'] : 'Unknown';
    $amount = $incomeRow ? floatval($incomeRow['amount']) : 0;
    $filePath = $incomeRow ? $incomeRow['attachment'] : null;

    $query = "DELETE FROM incomes WHERE id = $id";
    
    if ($conn->query($query)) {
        if ($filePath && file_exists('../' . $filePath)) {
            unlink('../' . $filePath);
        }
        log_action($conn, 'DELETE', 'incomes', $id, "Deleted income: $description (₹$amount)");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
