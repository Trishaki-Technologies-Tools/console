<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get total income
$income_query = "SELECT SUM(amount) as total, COUNT(*) as count FROM incomes";
$income_result = $conn->query($income_query);
$income_data = $income_result->fetch_assoc();
$total_income = $income_data['total'] ?? 0;
$income_count = $income_data['count'] ?? 0;

// Get total expenses
$expense_query = "SELECT SUM(amount) as total, COUNT(*) as count FROM expenses";
$expense_result = $conn->query($expense_query);
$expense_data = $expense_result->fetch_assoc();
$total_expenses = $expense_data['total'] ?? 0;
$expense_count = $expense_data['count'] ?? 0;

// Calculate overall balance
$balance = $total_income - $total_expenses;
$total_transactions = $income_count + $expense_count;

// Calculate Payment Mode Specific Balances
$modes = ['HDFC Bank', 'Cash'];
$mode_balances = [];

foreach ($modes as $mode) {
    // Income for mode
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

// This Month Income
$tm_inc_query = "SELECT SUM(amount) as total FROM incomes WHERE 1 $currentMonthSql";
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
