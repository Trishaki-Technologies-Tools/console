<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $employee_name = $_POST['employee_name'];
    $role = $_POST['role'];
    $month = $_POST['month'];
    $amount = floatval($_POST['amount']);
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $status = $_POST['status'];

    try {
        // First get the existing linked expense_id and old amount/date to handle report updates properly
        // Actually, simplest way for report update is:
        // 1. Get old expense_id. 
        // 2. Update salary log.
        // 3. Update expense log.
        // 4. Trigger report recalculation for BOTH old date (if changed) and new date.
        
        $getStmt = $conn->prepare("SELECT expense_id FROM salary_logs WHERE id = ?");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $expenseId = null;
        if ($row = $result->fetch_assoc()) {
            $expenseId = $row['expense_id'];
        }
        $getStmt->close();

        // Update Salary Log
        $stmt = $conn->prepare("UPDATE salary_logs SET employee_name=?, role=?, month=?, amount=?, payment_date=?, payment_mode=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $employee_name, $role, $month, $amount, $payment_date, $payment_mode, $status, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Update Linked Expense
            if ($expenseId) {
                // Get old date of expense for report recalc
                $oldExpDate = null;
                $eStmt = $conn->prepare("SELECT date FROM expenses WHERE id = ?");
                $eStmt->bind_param("i", $expenseId);
                $eStmt->execute();
                $res = $eStmt->get_result();
                if ($r = $res->fetch_assoc()) {
                     $oldExpDate = $r['date'];
                }
                $eStmt->close();

                // Update expense record
                $expenseDescription = "Salary: " . $employee_name . " (" . $role . ")";
                $expenseCategory = "Salary";
                $updateExp = $conn->prepare("UPDATE expenses SET description=?, amount=?, date=?, category=?, payment_mode=? WHERE id=?");
                $updateExp->bind_param("sdsssi", $expenseDescription, $amount, $payment_date, $expenseCategory, $payment_mode, $expenseId);
                $updateExp->execute();
                $updateExp->close();
                
                // Recalculate reports
                // 1. Recalculate for Old Date (if different from new date)
                if ($oldExpDate && date('Y-m', strtotime($oldExpDate)) != date('Y-m', strtotime($payment_date))) {
                    recalculateReport($conn, $oldExpDate);
                }
                
                // 2. Recalculate for New Date
                recalculateReport($conn, $payment_date);
            }
            
            echo json_encode(['success' => true, 'message' => 'Salary updated successfully']);
        } else {
             throw new Exception("Execute failed: " . $stmt->error);
        }

    } catch (Throwable $e) {
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
