<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $conn->begin_transaction();

        // 1. Get salary log details to find linked expense
        $getStmt = $conn->prepare("
            SELECT s.amount, s.payment_date, e.name AS employee_name, e.role 
            FROM salary_logs s 
            JOIN employees e ON s.employee_id = e.id 
            WHERE s.id = ?
        ");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $salaryLog = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();

        if ($salaryLog) {
            $amount = floatval($salaryLog['amount']);
            $date = $salaryLog['payment_date'];
            $name = $salaryLog['employee_name'];
            $role = $salaryLog['role'];
            
            // Find linked expense
            $expDesc = "Salary: " . $name . " (" . $role . ")";
            $expQuery = "SELECT id FROM expenses WHERE description = ? AND amount = ? AND date = ?";
            $expStmt = $conn->prepare($expQuery);
            $expStmt->bind_param("sds", $expDesc, $amount, $date);
            $expStmt->execute();
            $expRes = $expStmt->get_result()->fetch_assoc();
            $expStmt->close();

            if ($expRes) {
                $expenseId = $expRes['id'];
                
                // Delete transaction log
                $tStmt = $conn->prepare("DELETE FROM transactions WHERE reference_id = ? AND reference_table = 'expenses'");
                $tStmt->bind_param("i", $expenseId);
                $tStmt->execute();
                $tStmt->close();

                // Delete expense
                $delExp = $conn->prepare("DELETE FROM expenses WHERE id = ?");
                $delExp->bind_param("i", $expenseId);
                $delExp->execute();
                $delExp->close();
            }
        }

        // 2. Delete salary log
        $stmt = $conn->prepare("DELETE FROM salary_logs WHERE id = ?");
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->commit();
            
            // Recalculate report totals
            if ($salaryLog) {
                recalculateReport($conn, $date);
            }
            
            echo json_encode(['success' => true, 'message' => 'Salary log and linked expense deleted successfully']);
        } else {
             throw new Exception("Execute failed: " . $stmt->error);
        }

    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing ID parameter']);
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
