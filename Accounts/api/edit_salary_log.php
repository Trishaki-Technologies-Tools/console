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
        $stmt2 = $conn->prepare("INSERT INTO employees (name, designation) VALUES (?, ?)");
        $stmt2->bind_param("ss", $name, $role);
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
    $id = intval($_POST['id']);
    $employee_name = $_POST['employee_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $month = $_POST['month'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = $_POST['payment_mode'] ?? 'Bank Transfer';

    try {
        $conn->begin_transaction();

        // 1. Get old salary log and employee details
        $getStmt = $conn->prepare("
            SELECT s.amount, s.payment_date, e.name AS employee_name, e.designation AS role 
            FROM salary_logs s 
            JOIN employees e ON s.employee_id = e.id 
            WHERE s.id = ?
        ");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $oldLog = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();

        if (!$oldLog) {
            throw new Exception("Salary record not found");
        }

        $oldName = $oldLog['employee_name'];
        $oldRole = $oldLog['role'];
        $oldAmount = floatval($oldLog['amount']);
        $oldDate = $oldLog['payment_date'];

        // 2. Resolve employee ID (insert if new)
        $employee_id = getEmployeeId($conn, $employee_name, $role, $amount);

        // 3. Find and update linked expense
        $oldExpDesc = "Salary: " . $oldName . " (" . $oldRole . ")";
        $expQuery = "SELECT id FROM expenses WHERE description = ? AND amount = ? AND date = ?";
        $expStmt = $conn->prepare($expQuery);
        $expStmt->bind_param("sds", $oldExpDesc, $oldAmount, $oldDate);
        $expStmt->execute();
        $expRes = $expStmt->get_result()->fetch_assoc();
        $expStmt->close();

        if ($expRes) {
            $expenseId = $expRes['id'];
            $newExpDesc = "Salary: " . $employee_name . " (" . $role . ")";
            $category_id = getCategoryId($conn, "Salary");
            $payment_mode_id = getPaymentModeId($conn, $payment_mode);

            // Update expense
            $updExp = $conn->prepare("UPDATE expenses SET description = ?, amount = ?, date = ?, category_id = ?, payment_mode_id = ? WHERE id = ?");
            $updExp->bind_param("sdgiii", $newExpDesc, $amount, $payment_date, $category_id, $payment_mode_id, $expenseId);
            $updExp->execute();
            $updExp->close();

            // Update transaction log
            $tStmt = $conn->prepare("UPDATE transactions SET amount = ?, date = ?, description = ? WHERE reference_id = ? AND reference_table = 'expenses'");
            $tStmt->bind_param("dssi", $amount, $payment_date, $newExpDesc, $expenseId);
            $tStmt->execute();
            $tStmt->close();
        }

        // 4. Update Salary Log
        $stmt = $conn->prepare("UPDATE salary_logs SET employee_id = ?, amount = ?, net_salary = ?, payment_date = ?, payment_mode = ?, month_year = ? WHERE id = ?");
        $stmt->bind_param("iddsssi", $employee_id, $amount, $amount, $payment_date, $payment_mode, $month, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->commit();
            
            // Recalculate reports for old date (if changed) and new date
            if (date('Y-m', strtotime($oldDate)) != date('Y-m', strtotime($payment_date))) {
                recalculateReport($conn, $oldDate);
            }
            recalculateReport($conn, $payment_date);
            
            echo json_encode(['success' => true, 'message' => 'Salary updated successfully']);
        } else {
             throw new Exception("Execute failed: " . $stmt->error);
        }

    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
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
