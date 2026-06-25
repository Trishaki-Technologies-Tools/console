<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Detect loans_payments columns to build correct subquery
    $lpCols = [];
    $lpCheck = $conn->query("SHOW TABLES LIKE 'loans_payments'");
    $hasLoanPayments = ($lpCheck && $lpCheck->num_rows > 0);

    if ($hasLoanPayments) {
        $cr = $conn->query("SHOW COLUMNS FROM loans_payments");
        while ($c = $cr->fetch_assoc()) $lpCols[] = $c['Field'];
    }

    $typeCol = in_array('payment_type', $lpCols) ? 'payment_type' : 'type';
    $repayVal = in_array('payment_type', $lpCols) ? 'repayment' : 'principal';

    if ($hasLoanPayments) {
        $paidSubquery = "(SELECT COALESCE(SUM(amount), 0) FROM loans_payments WHERE loan_id = loans.id AND {$typeCol} = '{$repayVal}')";
        $lastInterestSubquery = "(SELECT MAX(payment_date) FROM loans_payments WHERE loan_id = loans.id AND {$typeCol} = 'interest')";
    } else {
        $paidSubquery = "0";
        $lastInterestSubquery = "NULL";
    }

    $sql = "SELECT 
                id,
                description AS creditor_name,
                principal_amount,
                interest_rate,
                'Monthly' AS interest_type,
                start_date,
                CASE WHEN status='active' THEN 'Active' ELSE 'Settled' END AS status,
                {$paidSubquery} AS paid_amount,
                {$lastInterestSubquery} AS last_interest_payment_date,
                '' AS description
            FROM loans
            ORDER BY created_at DESC";

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $loans = [];
    while ($row = $result->fetch_assoc()) {
        $loans[] = $row;
    }

    echo json_encode($loans);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
