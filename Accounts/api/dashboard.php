<?php
header('Content-Type: application/json');
require_once 'config.php';

// Helper function to run query and return single value
function getSingleValue($conn, $query, $key) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row[$key] ?? 0;
    }
    return 0;
}

// Parse Financial Year cookie
$selected_fy = $_COOKIE['financial_year'] ?? '';
if (preg_match('/^(\d{4})-(\d{4})$/', $selected_fy, $matches)) {
    $fy_start_year = intval($matches[1]);
    $fy_end_year = intval($matches[2]);
} else {
    $currentMonth = intval(date('n'));
    $currentYear = intval(date('Y'));
    $fy_start_year = ($currentMonth >= 4) ? $currentYear : $currentYear - 1;
    $fy_end_year = $fy_start_year + 1;
}

$fy_start_date = "$fy_start_year-04-01";
$fy_end_date = "$fy_end_year-03-31";

// Target month/year calculations relative to FY
$currentMonth = intval(date('n'));

// This month within selected FY
$target_year_this_month = ($currentMonth >= 4) ? $fy_start_year : $fy_end_year;
$this_month_start = sprintf("%04d-%02d-01", $target_year_this_month, $currentMonth);
$this_month_end = date('Y-m-t', strtotime($this_month_start));

// Last month within selected FY
$prevMonth = $currentMonth - 1;
if ($prevMonth == 0) {
    $prevMonth = 12;
}
$target_year_last_month = ($prevMonth >= 4) ? $fy_start_year : $fy_end_year;
$last_month_start = sprintf("%04d-%02d-01", $target_year_last_month, $prevMonth);
$last_month_end = date('Y-m-t', strtotime($last_month_start));

// --- 1. BALANCES BY PAYMENT METHOD ---
$modes_res = $conn->query("SELECT id, mode_name, opening_balance FROM payment_modes WHERE status='active'");
$payment_balances = [];
$total_inflow = getSingleValue($conn, "SELECT SUM(amount) as total FROM incomes", 'total');
$total_outflow = getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses", 'total');
$total_opening = getSingleValue($conn, "SELECT SUM(opening_balance) as total FROM payment_modes WHERE status='active'", 'total');
$total_balance = $total_inflow - $total_outflow + $total_opening;

if ($modes_res) {
    while ($m = $modes_res->fetch_assoc()) {
        $mode_id = $m['id'];
        $mode_name = $m['mode_name'];
        $opening = floatval($m['opening_balance']);
        
        $inc = getSingleValue($conn, "SELECT SUM(amount) as total FROM incomes WHERE payment_mode_id = $mode_id", 'total');
        $exp = getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses WHERE payment_mode_id = $mode_id", 'total');
        $bal = $opening + $inc - $exp;
        
        $payment_balances[] = [
            'name' => $mode_name,
            'balance' => number_format($bal, 2, '.', '')
        ];
    }
}

// --- 2. INCOME METRICS (excluding Opening Balance) ---
$category_exclude = "c.category_name != 'Opening Balance' OR c.category_name IS NULL";

$this_month_income = getSingleValue($conn, "SELECT SUM(i.amount) as total 
                 FROM incomes i
                 LEFT JOIN incomes_categories c ON i.category_id = c.id
                 WHERE ($category_exclude)
                   AND i.date >= '$this_month_start' AND i.date <= '$this_month_end'", 'total');

$last_month_income = getSingleValue($conn, "SELECT SUM(i.amount) as total 
                 FROM incomes i
                 LEFT JOIN incomes_categories c ON i.category_id = c.id
                 WHERE ($category_exclude)
                   AND i.date >= '$last_month_start' AND i.date <= '$last_month_end'", 'total');

$this_year_income = getSingleValue($conn, "SELECT SUM(i.amount) as total 
                 FROM incomes i
                 LEFT JOIN incomes_categories c ON i.category_id = c.id
                 WHERE ($category_exclude)
                   AND i.date >= '$fy_start_date' AND i.date <= '$fy_end_date'", 'total');

$total_income = getSingleValue($conn, "SELECT SUM(i.amount) as total 
                  FROM incomes i
                  LEFT JOIN incomes_categories c ON i.category_id = c.id
                  WHERE ($category_exclude)", 'total');


// --- 3. EXPENSE METRICS ---
$this_month_expense = getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses 
                 WHERE date >= '$this_month_start' AND date <= '$this_month_end'", 'total');

$last_month_expense = getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses 
                 WHERE date >= '$last_month_start' AND date <= '$last_month_end'", 'total');

$this_year_expense = getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses 
                 WHERE date >= '$fy_start_date' AND date <= '$fy_end_date'", 'total');

$total_expenses = getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses", 'total');


// --- 4. PROFIT / LOSS METRICS ---
$this_month_profit = $this_month_income - $this_month_expense;
$last_month_profit = $last_month_income - $last_month_expense;
$this_year_profit = $this_year_income - $this_year_expense;
$overall_profit = $total_income - $total_expenses;


// --- 5. LOANS METRICS ---
$active_loans_amount = getSingleValue($conn, "SELECT SUM(principal_amount) as total FROM loans WHERE status = 'active'", 'total');
$interest_paid_total = getSingleValue($conn, "SELECT SUM(amount) as total FROM loans_payments WHERE type = 'interest'", 'total');


// --- 6. OPERATIONAL COUNTS ---
$total_invoices_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM invoices WHERE invoice_date >= '$fy_start_date' AND invoice_date <= '$fy_end_date'", 'total');
$total_vouchers_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM vouchers WHERE date >= '$fy_start_date' AND date <= '$fy_end_date'", 'total');
$total_clients_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM clients WHERE created_at >= '$fy_start_date 00:00:00' AND created_at <= '$fy_end_date 23:59:59'", 'total');
$total_quotations_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM quotations WHERE quotation_date >= '$fy_start_date' AND quotation_date <= '$fy_end_date'", 'total');
$total_employees_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM employees WHERE status = 'active' AND created_at >= '$fy_start_date 00:00:00' AND created_at <= '$fy_end_date 23:59:59'", 'total');


echo json_encode([
    'total_balance' => number_format($total_balance, 2, '.', ''),
    'payment_balances' => $payment_balances,
    
    'this_month_income' => number_format($this_month_income, 2, '.', ''),
    'last_month_income' => number_format($last_month_income, 2, '.', ''),
    'this_year_income' => number_format($this_year_income, 2, '.', ''),
    'total_income' => number_format($total_income, 2, '.', ''),
    
    'this_month_expense' => number_format($this_month_expense, 2, '.', ''),
    'last_month_expense' => number_format($last_month_expense, 2, '.', ''),
    'this_year_expense' => number_format($this_year_expense, 2, '.', ''),
    'total_expenses' => number_format($total_expenses, 2, '.', ''),
    
    'this_month_profit' => number_format($this_month_profit, 2, '.', ''),
    'last_month_profit' => number_format($last_month_profit, 2, '.', ''),
    'this_year_profit' => number_format($this_year_profit, 2, '.', ''),
    'overall_profit' => number_format($overall_profit, 2, '.', ''),
    
    'active_loans_amount' => number_format($active_loans_amount, 2, '.', ''),
    'interest_paid_total' => number_format($interest_paid_total, 2, '.', ''),
    
    'total_invoices_count' => intval($total_invoices_count),
    'total_vouchers_count' => intval($total_vouchers_count),
    'total_clients_count' => intval($total_clients_count),
    'total_quotations_count' => intval($total_quotations_count),
    'total_employees_count' => intval($total_employees_count)
]);

$conn->close();
?>
