<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_name = $_POST['employee_name'] ?? '';
    $role = $_POST['role'] ?? '';
    $month = $_POST['month'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = $_POST['payment_mode'] ?? 'Bank Transfer';
    $status = $_POST['status'] ?? 'Paid';

    if (empty($employee_name) || empty($amount) || empty($payment_date)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO salary_logs (employee_name, role, month, amount, payment_date, payment_mode, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sssssss", $employee_name, $role, $month, $amount, $payment_date, $payment_mode, $status);

        if ($stmt->execute()) {
            $salaryLogId = $conn->insert_id; // Capture Salary Log ID first!

            // --- Sync with Expenses Table ---
            // Create description for expense
            $expenseDescription = "Salary: " . $employee_name . " (" . $role . ")";
            $expenseCategory = "Salary";
            
            // Insert into expenses
            $expStmt = $conn->prepare("INSERT INTO expenses (description, amount, date, category, payment_mode) VALUES (?, ?, ?, ?, ?)");
            if ($expStmt) {
                $expStmt->bind_param("sdsss", $expenseDescription, $amount, $payment_date, $expenseCategory, $payment_mode);
                if ($expStmt->execute()) {
                    $expenseId = $conn->insert_id;
                    $expStmt->close();
                    
                    // Update salary log with expense_id
                    $updateSalaryCtx = $conn->prepare("UPDATE salary_logs SET expense_id = ? WHERE id = ?");
                    if ($updateSalaryCtx) {
                         $updateSalaryCtx->bind_param("ii", $expenseId, $salaryLogId);
                         $updateSalaryCtx->execute();
                         $updateSalaryCtx->close();
                    }
                } else {
                     $expStmt->close();
                }
                
                // --- Update Reports (Same logic as add_expense.php) ---
                $expenseMonth = strtoupper(date('M-Y', strtotime($payment_date)));
                $firstDay = date('Y-m-01', strtotime($payment_date));
                $lastDay = date('Y-m-t', strtotime($payment_date));
                
                // Recalculate total expenses for that month
                $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date >= '$firstDay' AND date <= '$lastDay'";
                $expenseResult = $conn->query($expenseQuery);
                
                if ($expenseResult) {
                    $totalExpenses = $expenseResult->fetch_assoc()['total'];
                    
                    // Get report to update closing balance
                    $reportQuery = "SELECT opening_balance, income FROM reports WHERE month = '$expenseMonth'";
                    $reportResult = $conn->query($reportQuery);
                    
                    if ($reportResult && $reportResult->num_rows > 0) {
                        $report = $reportResult->fetch_assoc();
                        $closingBalance = $report['opening_balance'] + $report['income'] - $totalExpenses;
                        
                        // Update report
                        $updateQuery = "UPDATE reports SET expenses = $totalExpenses, closing_balance = $closingBalance WHERE month = '$expenseMonth'";
                        $conn->query($updateQuery);
                    }
                }
            }
            // -------------------------------

            echo json_encode(['success' => true, 'message' => 'Salary log added and synced to expenses successfully']);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
