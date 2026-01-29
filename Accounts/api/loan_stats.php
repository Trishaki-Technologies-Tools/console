<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Calculate Total Interest Paid from Expenses
    // We look for expenses with category 'Other' (or 'Interest' if we added it) and description containing 'Interest'
    // Or strictly rely on description format from pay_loan_interest.php: "Interest Payment: ..."
    
    $sql = "SELECT SUM(amount) as total_interest FROM expenses WHERE description LIKE 'Interest Payment:%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    $totalInterest = $row['total_interest'] ?? 0;

    echo json_encode(['success' => true, 'total_interest_paid' => floatval($totalInterest)]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
