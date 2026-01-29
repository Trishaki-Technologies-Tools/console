<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = intval($_POST['loan_id']);
    $payment_date = $_POST['payment_date'];
    
    try {
        // 1. Fetch Loan Details
        $lStmt = $conn->prepare("SELECT * FROM loans WHERE id = ?");
        $lStmt->bind_param("i", $loan_id);
        $lStmt->execute();
        $loan = $lStmt->get_result()->fetch_assoc();
        $lStmt->close();
        
        if (!$loan) {
            throw new Exception("Loan not found");
        }
        
        // 2. Calculate Interest Amount
        $principal = floatval($loan['principal_amount']);
        $rate = floatval($loan['interest_rate']);
        $type = $loan['interest_type']; // 'Monthly' or 'Annual'
        
        if ($type === 'Annual') {
            $monthlyRate = $rate / 12;
        } else {
            $monthlyRate = $rate;
        }
        
        $calculatedInterest = ($principal * $monthlyRate) / 100;
        
        // Use custom amount if provided, else use calculated
        if (isset($_POST['custom_amount']) && is_numeric($_POST['custom_amount'])) {
            $interestAmount = floatval($_POST['custom_amount']);
        } else {
            $interestAmount = $calculatedInterest;
        }
        
        // 3. Insert into Expenses
        $description = "Interest Payment: " . $loan['creditor_name'];
        $category = "Interest"; 
        $payment_mode = "Cash"; // Default
        
        $expStmt = $conn->prepare("INSERT INTO expenses (description, amount, date, category, payment_mode, loan_id) VALUES (?, ?, ?, ?, ?, ?)");
        $expStmt->bind_param("sdsssi", $description, $interestAmount, $payment_date, $category, $payment_mode, $loan_id);
        
        if ($expStmt->execute()) {
            $expStmt->close();
            
            // 4. Update Loan (Last Payment Date)
            $updStmt = $conn->prepare("UPDATE loans SET last_interest_payment_date = ? WHERE id = ?");
            $updStmt->bind_param("si", $payment_date, $loan_id);
            $updStmt->execute();
            $updStmt->close();
            
            // 5. Update Reports
            recalculateReport($conn, $payment_date);
            
            echo json_encode(['success' => true, 'message' => 'Interest payment recorded']);
        } else {
            throw new Exception("Expense insert failed: " . $conn->error);
        }
        
    } catch (Throwable $e) {
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
