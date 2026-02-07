<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get total income (Real Earnings, excluding Opening Balance)
$income_query = "SELECT SUM(amount) as total, COUNT(*) as count FROM incomes WHERE category != 'Opening Balance'";
$income_result = $conn->query($income_query);
$income_data = $income_result->fetch_assoc();
$total_income = $income_data['total'] ?? 0;
$income_count = $income_data['count'] ?? 0;

// Get total inflow (Earnings + Opening Balance) for Available Funds calculation
$inflow_query = "SELECT SUM(amount) as total FROM incomes";
$inflow_result = $conn->query($inflow_query);
$inflow_data = $inflow_result->fetch_assoc();
$total_inflow = $inflow_data['total'] ?? 0;

// Get total expenses
$expense_query = "SELECT SUM(amount) as total, COUNT(*) as count FROM expenses";
$expense_result = $conn->query($expense_query);
$expense_data = $expense_result->fetch_assoc();
$total_expenses = $expense_data['total'] ?? 0;
$expense_count = $expense_data['count'] ?? 0;

// Calculate overall balance (Funds Available)
$balance = $total_inflow - $total_expenses;

// Calculate Net Profit (Earnings - Expenses)
$overall_profit = $total_income - $total_expenses;

$total_transactions = $income_count + $expense_count;

// Calculate Payment Mode Specific Balances (Includes Opening Balance)
$modes = ['HDFC Bank', 'Cash'];
$mode_balances = [];

foreach ($modes as $mode) {
    // Income for mode (Includes Opening Balance)
    $qs = "SELECT SUM(amount) as total FROM incomes WHERE payment_mode = '$mode'";
    $rs = $conn->query($qs);
    $inc = $rs->fetch_assoc()['total'] ?? 0;
    
    // Expense for mode
    $qs = "SELECT SUM(amount) as total FROM expenses WHERE payment_mode = '$mode'";
    $rs = $conn->query($qs);
    $exp = $rs->fetch_assoc()['total'] ?? 0;
    
    $mode_balances[$mode] = $inc - $exp;
}

// Calculate This Month's Stats
$currentMonthSql = "AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";

// This Month Income (Excluding Opening Balance)
$tm_inc_query = "SELECT SUM(amount) as total FROM incomes WHERE category != 'Opening Balance' $currentMonthSql";
$tm_inc_res = $conn->query($tm_inc_query);
$tm_income = $tm_inc_res->fetch_assoc()['total'] ?? 0;

// This Month Expense
$tm_exp_query = "SELECT SUM(amount) as total FROM expenses WHERE 1 $currentMonthSql";
$tm_exp_res = $conn->query($tm_exp_query);
$tm_expense = $tm_exp_res->fetch_assoc()['total'] ?? 0;

$tm_profit = $tm_income - $tm_expense;

echo json_encode([
    'total_income' => number_format($total_income, 2),
    'total_expenses' => number_format($total_expenses, 2),
    'balance' => number_format($balance, 2),
    'overall_profit' => number_format($overall_profit, 2),
    'total_transactions' => $total_transactions,
    'income_count' => $income_count,
    'expense_count' => $expense_count,
    'hdfc_balance' => number_format($mode_balances['HDFC Bank'], 2),
    'cash_balance' => number_format($mode_balances['Cash'], 2),
    'this_month_expense' => number_format($tm_expense, 2),
    'this_month_profit' => number_format($tm_profit, 2)
]);

$conn->close();
?>
