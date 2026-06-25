<?php
header('Content-Type: application/json');
require_once 'config.php';

function getEmployeeId($conn, $name, $role, $salary) {
    $stmt = $conn->prepare("SELECT id FROM employees WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO employees (name, role, salary) VALUES (?, ?, ?)");
        $stmt2->bind_param("ssd", $name, $role, $salary);
        $stmt2->execute();
        return $conn->insert_id;
    }
}

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
    $employee_name = trim($_POST['employee_name'] ?? '');
    $month = $_POST['month'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = $_POST['payment_mode'] ?? 'Bank Transfer';

    if (empty($employee_name) || empty($amount) || empty($payment_date)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Get or create employee
        $employee_id = getEmployeeId($conn, $employee_name, '', $amount);

        // Detect actual salary_logs columns once
        $cols = [];
        $colResult = $conn->query("SHOW COLUMNS FROM salary_logs");
        while ($c = $colResult->fetch_assoc()) $cols[] = $c['Field'];

        $hasModeId     = in_array('payment_mode_id', $cols);
        $hasModeText   = in_array('payment_mode', $cols);
        $hasBonus      = in_array('bonus', $cols);
        $hasDeductions = in_array('deductions', $cols);
        $hasNetSalary  = in_array('net_salary', $cols);
        $hasMonthYear  = in_array('month_year', $cols);
        $hasMonthCol   = in_array('month', $cols);

        // Resolve payment mode ID if needed
        $payment_mode_id = null;
        if ($hasModeId) {
            $payment_mode_id = getPaymentModeId($conn, $payment_mode);
        }

        // Build SQL with only existing columns — use separate queries per schema variant
        if ($hasModeId && ($hasMonthYear || $hasMonthCol)) {
            $monthCol = $hasMonthYear ? 'month_year' : 'month';
            $bonusCols = ($hasBonus ? ', bonus' : '') . ($hasDeductions ? ', deductions' : '') . ($hasNetSalary ? ', net_salary' : '');
            $bonusVals = ($hasBonus ? ', 0' : '') . ($hasDeductions ? ', 0' : '') . ($hasNetSalary ? ', ?' : '');
            $sql = "INSERT INTO salary_logs (employee_id, amount{$bonusCols}, payment_date, payment_mode_id, {$monthCol}) VALUES (?, ?{$bonusVals}, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            if ($hasNetSalary) {
                $stmt->bind_param("iddsis", $employee_id, $amount, $amount, $payment_date, $payment_mode_id, $month);
            } else {
                $stmt->bind_param("idsis", $employee_id, $amount, $payment_date, $payment_mode_id, $month);
            }
        } elseif ($hasModeText && ($hasMonthYear || $hasMonthCol)) {
            $monthCol = $hasMonthYear ? 'month_year' : 'month';
            $sql = "INSERT INTO salary_logs (employee_id, amount, payment_date, payment_mode, {$monthCol}) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("idsss", $employee_id, $amount, $payment_date, $payment_mode, $month);
        } elseif ($hasModeId) {
            $sql = "INSERT INTO salary_logs (employee_id, amount, payment_date, payment_mode_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("idsi", $employee_id, $amount, $payment_date, $payment_mode_id);
        } elseif ($hasModeText) {
            $sql = "INSERT INTO salary_logs (employee_id, amount, payment_date, payment_mode) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("idss", $employee_id, $amount, $payment_date, $payment_mode);
        } else {
            // Bare minimum — only confirmed columns
            $sql = "INSERT INTO salary_logs (employee_id, amount, payment_date) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("ids", $employee_id, $amount, $payment_date);
        }

        if ($stmt->execute()) {
            $salaryLogId = $conn->insert_id;

            // Sync with Expenses Table
            $expenseDescription = "Salary: " . $employee_name;
            $category_id = getCategoryId($conn, "Salary");
            $exp_mode_id = $payment_mode_id ?? getPaymentModeId($conn, $payment_mode);
            
            $expStmt = $conn->prepare("INSERT INTO expenses (description, amount, date, category_id, payment_mode_id) VALUES (?, ?, ?, ?, ?)");
            if ($expStmt) {
                $expStmt->bind_param("sdsii", $expenseDescription, $amount, $payment_date, $category_id, $exp_mode_id);
                if ($expStmt->execute()) {
                    $expenseId = $conn->insert_id;
                    $expStmt->close();
                    
                    // Log to transactions table
                    $tStmt = $conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('expense', ?, ?, ?, 'expenses', ?)");
                    $tStmt->bind_param("dsis", $amount, $payment_date, $expenseId, $expenseDescription);
                    $tStmt->execute();
                } else {
                    $expStmt->close();
                }
            }

            $conn->commit();

            // Update Reports (Same logic as add_expense.php)
            $expenseMonth = strtoupper(date('M-Y', strtotime($payment_date)));
            $firstDay = date('Y-m-01', strtotime($payment_date));
            $lastDay = date('Y-m-t', strtotime($payment_date));
            
            $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date >= '$firstDay' AND date <= '$lastDay'";
            $expenseResult = $conn->query($expenseQuery);
            
            if ($expenseResult) {
                $totalExpenses = $expenseResult->fetch_assoc()['total'];
                
                $reportQuery = "SELECT opening_balance, income FROM reports WHERE month = '$expenseMonth'";
                $reportResult = $conn->query($reportQuery);
                
                if ($reportResult && $reportResult->num_rows > 0) {
                    $report = $reportResult->fetch_assoc();
                    $closingBalance = $report['opening_balance'] + $report['income'] - $totalExpenses;
                    $updateQuery = "UPDATE reports SET expenses = $totalExpenses, closing_balance = $closingBalance WHERE month = '$expenseMonth'";
                    $conn->query($updateQuery);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Salary log added and synced to expenses successfully']);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
