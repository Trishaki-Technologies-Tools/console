<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // First get the linked expense_id
        $getStmt = $conn->prepare("SELECT expense_id FROM salary_logs WHERE id = ?");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $result = $getStmt->get_result();
        $expenseId = null;
        
        if ($row = $result->fetch_assoc()) {
            $expenseId = $row['expense_id'];
        }
        $getStmt->close();

        // Delete from salary_logs
        $stmt = $conn->prepare("DELETE FROM salary_logs WHERE id = ?");
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            
            // Delete linked expense if it exists
            if ($expenseId) {
                // Get expense date for report update BEFORE deleting
                $dateStmt = $conn->prepare("SELECT date FROM expenses WHERE id = ?");
                $dateStmt->bind_param("i", $expenseId);
                $dateStmt->execute();
                $dateResult = $dateStmt->get_result();
                $expenseDate = null;
                if ($dRow = $dateResult->fetch_assoc()) {
                    $expenseDate = $dRow['date'];
                }
                $dateStmt->close();

                // Delete expense
                $delExpStmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
                $delExpStmt->bind_param("i", $expenseId);
                $delExpStmt->execute();
                $delExpStmt->close();
                
                // Update Reports if we have a date
                if ($expenseDate) {
                    $expenseMonth = strtoupper(date('M-Y', strtotime($expenseDate)));
                    $firstDay = date('Y-m-01', strtotime($expenseDate));
                    $lastDay = date('Y-m-t', strtotime($expenseDate));
                    
                    // Recalculate total expenses for that month
                    $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE date >= '$firstDay' AND date <= '$lastDay'";
                    $expenseResult = $conn->query($expenseQuery);
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

            echo json_encode(['success' => true, 'message' => 'Salary log and linked expense deleted successfully']);
        } else {
             throw new Exception("Execute failed: " . $stmt->error);
        }

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing ID parameter']);
}

$conn->close();
?>
