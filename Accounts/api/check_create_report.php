<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get current month and year in IST
$currentMonth = strtoupper(date('M-Y')); // e.g., JAN-2026
$currentYear = date('Y');
$currentMonthNum = date('m');

// Check if report exists for current month
$checkQuery = "SELECT id FROM reports WHERE month = '$currentMonth'";
$result = $conn->query($checkQuery);

if ($result->num_rows == 0) {
    // Report doesn't exist, create it
    
    // Get previous month's closing balance as opening balance
    $prevMonthDate = date('M-Y', strtotime('-1 month'));
    $prevMonth = strtoupper($prevMonthDate);
    
    $prevBalanceQuery = "SELECT closing_balance FROM reports WHERE month = '$prevMonth' ORDER BY id DESC LIMIT 1";
    $prevResult = $conn->query($prevBalanceQuery);
    
    $openingBalance = 0;
    if ($prevResult && $prevResult->num_rows > 0) {
        $prevRow = $prevResult->fetch_assoc();
        $openingBalance = $prevRow['closing_balance'];
    }
    
    // Get current month's income and expenses
    $firstDay = date('Y-m-01');
    $lastDay = date('Y-m-t');
    
    // Calculate income for current month
    $incomeQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes 
                    WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $incomeResult = $conn->query($incomeQuery);
    $income = $incomeResult->fetch_assoc()['total'];
    
    // Calculate expenses for current month
    $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                     WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $expenseResult = $conn->query($expenseQuery);
    $expenses = $expenseResult->fetch_assoc()['total'];
    
    // Calculate closing balance
    $closingBalance = $openingBalance + $income - $expenses;
    
    // Insert new report
    $insertQuery = "INSERT INTO reports (month, opening_balance, income, expenses, closing_balance) 
                    VALUES ('$currentMonth', $openingBalance, $income, $expenses, $closingBalance)";
    
    if ($conn->query($insertQuery)) {
        echo json_encode([
            'success' => true, 
            'message' => 'New month report created', 
            'month' => $currentMonth,
            'opening_balance' => $openingBalance,
            'previous_month' => $prevMonth
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    // Report exists, update it with current month's data
    $firstDay = date('Y-m-01');
    $lastDay = date('Y-m-t');
    
    // Get opening balance from the existing report (don't change it)
    $reportQuery = "SELECT opening_balance FROM reports WHERE month = '$currentMonth'";
    $reportResult = $conn->query($reportQuery);
    $openingBalance = $reportResult->fetch_assoc()['opening_balance'];
    
    // Calculate current income
    $incomeQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes 
                    WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $incomeResult = $conn->query($incomeQuery);
    $income = $incomeResult->fetch_assoc()['total'];
    
    // Calculate current expenses
    $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                     WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $expenseResult = $conn->query($expenseQuery);
    $expenses = $expenseResult->fetch_assoc()['total'];
    
    // Calculate closing balance
    $closingBalance = $openingBalance + $income - $expenses;
    
    // Update existing report
    $updateQuery = "UPDATE reports 
                    SET income = $income, expenses = $expenses, closing_balance = $closingBalance 
                    WHERE month = '$currentMonth'";
    
    if ($conn->query($updateQuery)) {
        echo json_encode(['success' => true, 'message' => 'Report updated', 'month' => $currentMonth]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
