<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'settlement_helper.php';
require_once 'receipt_utils.php';

// Helper function to run query and return single value
function getSingleValue($conn, $query, $key) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        return $row[$key] ?? 0;
    }
    return 0;
}

// Reconcile settlements
try {
    reconcileMerchantSettlements($conn);
} catch (Throwable $e) {}

ensureReceiptsTableExists($conn);

// Parse Financial Year
$fy = getFinancialYearDates();
$fy_start_year = $fy['start_year'];
$fy_end_year = $fy['end_year'];
$fy_start_date = $fy['start_date'];
$fy_end_date = $fy['end_date'];

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

// --- 1. BALANCES BY PAYMENT METHOD (Global / All-Time) ---
$modes_res = $conn->query("SELECT id, mode_name, opening_balance FROM payment_modes WHERE status='active'");
$payment_balances = [];

if ($modes_res) {
    while ($m = $modes_res->fetch_assoc()) {
        $mode_id = $m['id'];
        $mode_name = $m['mode_name'];
        $bal = calculatePaymentModeBalance($conn, $mode_id);
        
        $payment_balances[] = [
            'name' => $mode_name,
            'balance' => number_format($bal, 2, '.', '')
        ];
    }
}

$total_balance = array_sum(array_column($payment_balances, 'balance'));

// Helper to compute unified total income for any date range (Manual Incomes + Receipts + Merchant Settlements)
function getUnifiedIncomeForRange($conn, $startDate, $endDate) {
    // 1. Manual Incomes
    $catExclude = "c.category_name != 'Opening Balance' OR c.category_name IS NULL";
    $manual = floatval(getSingleValue($conn, "SELECT SUM(i.amount) as total 
                 FROM incomes i
                 LEFT JOIN incomes_categories c ON i.category_id = c.id
                 WHERE ($catExclude)
                   AND i.date >= '$startDate' AND i.date <= '$endDate'", 'total'));
    
    // 2. Merchant Settlements Inflow to Bank
    $settlements = floatval(getSingleValue($conn, "SELECT SUM(s.amount) as total 
                 FROM merchant_settlements s
                 WHERE COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) <= NOW()
                   AND s.amount > 0
                   AND s.settlement_date >= '$startDate' AND s.settlement_date <= '$endDate'", 'total'));
    
    // 3. Payment Receipts
    $receiptsQuery = "SELECT items, cumulative_total_paid FROM receipts WHERE receipt_date >= '$startDate' AND receipt_date <= '$endDate'";
    $rRes = $conn->query($receiptsQuery);
    $receiptsTotal = 0;
    if ($rRes && $rRes->num_rows > 0) {
        while ($rRow = $rRes->fetch_assoc()) {
            $rItems = json_decode($rRow['items'] ?? '[]', true);
            $thisRecAmt = 0;
            if (is_array($rItems)) {
                foreach ($rItems as $ritm) {
                    $thisRecAmt += floatval($ritm['paidAmt'] ?? $ritm['amount'] ?? $ritm['totalInclTax'] ?? 0);
                }
            }
            if ($thisRecAmt <= 0) {
                $thisRecAmt = floatval($rRow['cumulative_total_paid']);
            }
            $receiptsTotal += $thisRecAmt;
        }
    }
    
    return $manual + $settlements + $receiptsTotal;
}

// Helper to compute unified total expense for any date range (Manual Expenses + Merchant Settlement Debits)
function getUnifiedExpenseForRange($conn, $startDate, $endDate) {
    // 1. Manual Expenses
    $manual = floatval(getSingleValue($conn, "SELECT SUM(amount) as total FROM expenses 
                 WHERE date >= '$startDate' AND date <= '$endDate'", 'total'));
                 
    // 2. Merchant Settlements Outflow from Merchant Account
    $settlements = floatval(getSingleValue($conn, "SELECT SUM(s.amount) as total 
                 FROM merchant_settlements s
                 WHERE COALESCE(s.settlement_datetime, CONCAT(s.settlement_date, ' 05:30:00')) <= NOW()
                   AND s.amount > 0
                   AND s.settlement_date >= '$startDate' AND s.settlement_date <= '$endDate'", 'total'));
                   
    return $manual + $settlements;
}

// --- 2. INCOME METRICS ---
$this_month_income = getUnifiedIncomeForRange($conn, $this_month_start, $this_month_end);
$last_month_income = getUnifiedIncomeForRange($conn, $last_month_start, $last_month_end);
$this_year_income = getUnifiedIncomeForRange($conn, $fy_start_date, $fy_end_date);
$total_income = $this_year_income; // Scoped to selected FY

// --- 3. EXPENSE METRICS ---
$this_month_expense = getUnifiedExpenseForRange($conn, $this_month_start, $this_month_end);
$last_month_expense = getUnifiedExpenseForRange($conn, $last_month_start, $last_month_end);
$this_year_expense = getUnifiedExpenseForRange($conn, $fy_start_date, $fy_end_date);
$total_expenses = $this_year_expense; // Scoped to selected FY

// --- 4. PROFIT / LOSS METRICS ---
$this_month_profit = $this_month_income - $this_month_expense;
$last_month_profit = $last_month_income - $last_month_expense;
$this_year_profit = $this_year_income - $this_year_expense;
$overall_profit = $total_income - $total_expenses;

// --- 5. LOANS METRICS ---
$active_loans_amount = getSingleValue($conn, "SELECT SUM(principal_amount) as total FROM loans WHERE status = 'active'", 'total');
$interest_paid_total = getSingleValue($conn, "SELECT SUM(amount) as total FROM loans_payments WHERE type = 'interest'", 'total');

// --- 6. OPERATIONAL COUNTS ---
$total_invoices_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM invoices WHERE (invoice_date >= '$fy_start_date' AND invoice_date <= '$fy_end_date') OR (invoice_date IS NULL AND DATE(created_at) >= '$fy_start_date' AND DATE(created_at) <= '$fy_end_date')", 'total');
$total_vouchers_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM vouchers WHERE date >= '$fy_start_date' AND date <= '$fy_end_date'", 'total');
$total_clients_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM clients WHERE (client_type = 'Client' OR client_type IS NULL OR client_type = '') AND created_at >= '$fy_start_date 00:00:00' AND created_at <= '$fy_end_date 23:59:59'", 'total');
$total_students_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM clients WHERE client_type = 'Student' AND created_at >= '$fy_start_date 00:00:00' AND created_at <= '$fy_end_date 23:59:59'", 'total');
$total_quotations_count = getSingleValue($conn, "SELECT COUNT(*) as total FROM quotations WHERE quotation_date >= '$fy_start_date' AND quotation_date <= '$fy_end_date'", 'total');

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
    'total_students_count' => intval($total_students_count),
    'total_quotations_count' => intval($total_quotations_count)
]);

$conn->close();
?>
