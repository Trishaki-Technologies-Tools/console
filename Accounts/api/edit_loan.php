<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $creditor_name = $_POST['creditor_name'] ?? '';
    $principal_amount = floatval($_POST['principal_amount'] ?? 0);
    $interest_rate = floatval($_POST['interest_rate'] ?? 0);
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $status = strtolower($_POST['status'] ?? 'active');
    
    // Map status
    if ($status === 'active') {
        $status = 'active';
    } else {
        $status = 'settled';
    }

    try {
        $conn->begin_transaction();

        // 1. Get existing loan details
        $getStmt = $conn->prepare("SELECT description AS creditor_name, principal_amount, start_date FROM loans WHERE id = ?");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $loanRow = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();
        
        if (!$loanRow) {
            throw new Exception("Loan not found");
        }
        
        $oldCreditor = $loanRow['creditor_name'];
        $oldAmount = floatval($loanRow['principal_amount']);
        $oldDate = $loanRow['start_date'];

        // 2. Find and Update Linked Income
        $oldIncDesc = "Loan from " . $oldCreditor;
        $incQuery = "SELECT id FROM incomes WHERE description = ? AND amount = ? AND date = ?";
        $incStmt = $conn->prepare($incQuery);
        $incStmt->bind_param("sds", $oldIncDesc, $oldAmount, $oldDate);
        $incStmt->execute();
        $incRes = $incStmt->get_result()->fetch_assoc();
        $incStmt->close();
        
        if ($incRes) {
            $incomeId = $incRes['id'];
            $newIncDesc = "Loan from " . $creditor_name;
            
            $updInc = $conn->prepare("UPDATE incomes SET description = ?, amount = ?, date = ? WHERE id = ?");
            $updInc->bind_param("sdsi", $newIncDesc, $principal_amount, $start_date, $incomeId);
            $updInc->execute();
            $updInc->close();

            // Update transaction log
            $tStmt = $conn->prepare("UPDATE transactions SET amount = ?, date = ?, description = ? WHERE reference_id = ? AND reference_table = 'incomes'");
            $tStmt->bind_param("dssi", $principal_amount, $start_date, $newIncDesc, $incomeId);
            $tStmt->execute();
        }

        // 3. Update Loan
        $stmt = $conn->prepare("UPDATE loans SET description = ?, principal_amount = ?, interest_rate = ?, start_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sddssi", $creditor_name, $principal_amount, $interest_rate, $start_date, $status, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->commit();
            
            // 4. Update Reports
            if (date('Y-m', strtotime($oldDate)) != date('Y-m', strtotime($start_date))) {
                recalculateReport($conn, date('Y-m-d', strtotime($oldDate)));
            }
            recalculateReport($conn, $start_date);

            echo json_encode(['success' => true, 'message' => 'Loan updated successfully']);
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
