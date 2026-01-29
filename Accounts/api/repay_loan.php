<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = intval($_POST['loan_id']);
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }

    try {
        // 1. Fetch Loan
        $stmt = $conn->prepare("SELECT * FROM loans WHERE id = ?");
        $stmt->bind_param("i", $loan_id);
        $stmt->execute();
        $loan = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$loan) {
            throw new Exception("Loan not found");
        }
        
        // 2. Insert Expense (Repayment)
        $description = "Loan Repayment: " . $loan['creditor_name'];
        $category = "Principal Amount"; 
        $payment_mode = "Cash"; 
        
        $expStmt = $conn->prepare("INSERT INTO expenses (description, amount, date, category, payment_mode, loan_id) VALUES (?, ?, ?, ?, ?, ?)");
        $expStmt->bind_param("sdsssi", $description, $amount, $payment_date, $category, $payment_mode, $loan_id);
        
        if (!$expStmt->execute()) {
             throw new Exception("Expense insert failed: " . $conn->error);
        }
        $expStmt->close();
        
        // 3. Update Loan (Paid Amount & Check Status)
        $newPaidAmount = floatval($loan['paid_amount']) + $amount;
        $principal = floatval($loan['principal_amount']);
        
        $newStatus = $loan['status'];
        // Floating point comparison tolerance
        if ($newPaidAmount >= ($principal - 0.01)) { 
            $newStatus = 'Closed';
        }
        
        $updStmt = $conn->prepare("UPDATE loans SET paid_amount = ?, status = ? WHERE id = ?");
        $updStmt->bind_param("dsi", $newPaidAmount, $newStatus, $loan_id);
        
        if ($updStmt->execute()) {
             // 4. Update Reports
             recalculateReport($conn, $payment_date);
             
             echo json_encode(['success' => true, 'message' => 'Repayment recorded', 'new_status' => $newStatus]);
        } else {
             throw new Exception("Loan update failed: " . $conn->error);
        }
        $updStmt->close();

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function recalculateReport($conn, $date) {
    // Standard Report Recalculation
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
