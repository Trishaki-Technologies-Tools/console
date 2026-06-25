<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $conn->begin_transaction();

        // 1. Get loan details to find linked income
        $getStmt = $conn->prepare("SELECT description AS creditor_name, principal_amount, start_date FROM loans WHERE id = ?");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $loan = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();
        
        if ($loan) {
            $creditor = $loan['creditor_name'];
            $amount = floatval($loan['principal_amount']);
            $date = $loan['start_date'];
            
            // 2. Find and delete linked income
            $incDesc = "Loan from " . $creditor;
            $incQuery = "SELECT id FROM incomes WHERE description = ? AND amount = ? AND date = ?";
            $incStmt = $conn->prepare($incQuery);
            $incStmt->bind_param("sds", $incDesc, $amount, $date);
            $incStmt->execute();
            $incRes = $incStmt->get_result()->fetch_assoc();
            $incStmt->close();
            
            if ($incRes) {
                $incomeId = $incRes['id'];
                
                // Delete transaction log
                $tStmt = $conn->prepare("DELETE FROM transactions WHERE reference_id = ? AND reference_table = 'incomes'");
                $tStmt->bind_param("i", $incomeId);
                $tStmt->execute();
                $tStmt->close();

                // Delete income
                $delInc = $conn->prepare("DELETE FROM incomes WHERE id = ?");
                $delInc->bind_param("i", $incomeId);
                $delInc->execute();
                $delInc->close();
            }
        }

        // 3. Delete loan
        $stmt = $conn->prepare("DELETE FROM loans WHERE id = ?");
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            $conn->commit();
            
            // Update report totals for the month
            if ($loan) {
                recalculateReport($conn, $date);
            }
            
            echo json_encode(['success' => true, 'message' => 'Loan deleted successfully']);
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
    
    $incQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $incResult = $conn->query($incQuery);
    $totalIncome = $incResult->fetch_assoc()['total'];
    
    $reportQuery = "SELECT opening_balance, expenses FROM reports WHERE month = '$month'";
    $reportResult = $conn->query($reportQuery);
    
    if ($reportResult && $reportResult->num_rows > 0) {
        $report = $reportResult->fetch_assoc();
        $closingBalance = $report['opening_balance'] + $totalIncome - $report['expenses'];
        
        $updateQuery = "UPDATE reports SET income = $totalIncome, closing_balance = $closingBalance WHERE month = '$month'";
        $conn->query($updateQuery);
    }
}

$conn->close();
?>
