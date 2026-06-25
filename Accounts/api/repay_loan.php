<?php
header('Content-Type: application/json');
require_once 'config.php';

function getCategoryId($conn, $name) {
    $stmt = $conn->prepare("SELECT id FROM expenses_categories WHERE category_name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO expenses_categories (category_name) VALUES (?)");
        $stmt2->bind_param("s", $name);
        $stmt2->execute();
        return $conn->insert_id;
    }
}

function getPaymentModeId($conn, $name) {
    $stmt = $conn->prepare("SELECT id FROM payment_modes WHERE mode_name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO payment_modes (mode_name) VALUES (?)");
        $stmt2->bind_param("s", $name);
        $stmt2->execute();
        return $conn->insert_id;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = intval($_POST['loan_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // 1. Fetch Loan
        $stmt = $conn->prepare("SELECT * FROM loans WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$loan) {
            throw new Exception("Loan not found");
        }
        
        // 2. Insert into loans_payments table
        $lpStmt = $conn->prepare("INSERT INTO loans_payments (loan_id, payment_date, amount, payment_type) VALUES (?, ?, ?, 'repayment')");
        $lpStmt->bind_param("isd", $loan_id, $payment_date, $amount);
        if (!$lpStmt->execute()) {
            throw new Exception("Loan payment insert failed");
        }
        $lpStmt->close();

        // 3. Insert Expense (Repayment)
        $description = "Loan Repayment: " . $loan['description']; // creditor_name mapped to description
        $category_id = getCategoryId($conn, "Principal Amount"); 
        $payment_mode_id = getPaymentModeId($conn, "Cash"); 
        
        $expStmt = $conn->prepare("INSERT INTO expenses (description, amount, date, category_id, payment_mode_id) VALUES (?, ?, ?, ?, ?)");
        $expStmt->bind_param("sdgii", $description, $amount, $payment_date, $category_id, $payment_mode_id);
        
        if (!$expStmt->execute()) {
             throw new Exception("Expense insert failed: " . $conn->error);
        }
        $expenseId = $conn->insert_id;
        $expStmt->close();

        // Log to transactions table
        $tStmt = $conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('expense', ?, ?, ?, 'expenses', ?)");
        $tStmt->bind_param("dsis", $amount, $payment_date, $expenseId, $description);
        $tStmt->execute();
        
        // 4. Calculate total repayment to see if loan is settled
        $totalPaidQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM loans_payments WHERE loan_id = ? AND payment_type = 'repayment'";
        $tpStmt = $conn->prepare($totalPaidQuery);
        $tpStmt->bind_param("i", $loan_id);
        $tpStmt->execute();
        $totalPaid = $tpStmt->get_result()->fetch_assoc()['total'];
        $tpStmt->close();

        $principal = floatval($loan['principal_amount']);
        $newStatus = 'active';
        if ($totalPaid >= ($principal - 0.01)) { 
            $newStatus = 'settled';
            
            $updStmt = $conn->prepare("UPDATE loans SET status = 'settled' WHERE id = ?");
            $updStmt->bind_param("i", $loan_id);
            $updStmt->execute();
            $updStmt->close();
        }
        
        $conn->commit();
        
        // Update reports
        recalculateReport($conn, $payment_date);
        
        echo json_encode(['success' => true, 'message' => 'Repayment recorded', 'new_status' => ($newStatus === 'settled' ? 'Settled' : 'Active')]);

    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function recalculateReport($conn, $date) {
    $month = strtoupper(date('M-Y', strtotime($date)));
    $firstDay = date('Y-m-01', strtotime($date));
    $lastDay = date('Y-m-t', strtotime($date));
    
    $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $expenseResult = $conn->query($expenseQuery);
    $totalExpenses = $expenseResult->fetch_assoc()['total'];
    
    $reportQuery = "SELECT opening_balance, income FROM reports WHERE month = '$month'";
    $reportResult = $conn->query($reportQuery);
    
    if ($reportResult && $reportResult->num_rows > 0) {
        $report = $reportResult->fetch_assoc();
        $closingBalance = $report['opening_balance'] + $report['income'] - $totalExpenses;
        
        $updateQuery = "UPDATE reports SET expenses = $totalExpenses, closing_balance = $closingBalance WHERE month = '$month'";
        $conn->query($updateQuery);
    }
}

$conn->close();
?>
