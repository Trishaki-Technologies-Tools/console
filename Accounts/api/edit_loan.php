<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $creditor_name = $_POST['creditor_name'];
    $principal_amount = floatval($_POST['principal_amount']);
    $interest_rate = floatval($_POST['interest_rate']);
    $interest_type = $_POST['interest_type'];
    $start_date = $_POST['start_date'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    try {
        // 1. Get existing loan linkage
        $getStmt = $conn->prepare("SELECT income_id, start_date FROM loans WHERE id = ?");
        $getStmt->bind_param("i", $id);
        $getStmt->execute();
        $loanRow = $getStmt->get_result()->fetch_assoc();
        $getStmt->close();
        
        $incomeId = $loanRow['income_id'];
        $oldDate = $loanRow['start_date'];

        // 2. Update Loan
        $stmt = $conn->prepare("UPDATE loans SET creditor_name=?, principal_amount=?, interest_rate=?, interest_type=?, start_date=?, description=?, status=? WHERE id=?");
        $stmt->bind_param("sdsssssi", $creditor_name, $principal_amount, $interest_rate, $interest_type, $start_date, $description, $status, $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // 3. Update Linked Income
            if ($incomeId) {
                $incomeDescription = "Loan from " . $creditor_name;
                // Update income record
                $updInc = $conn->prepare("UPDATE incomes SET description=?, amount=?, date=? WHERE id=?");
                $updInc->bind_param("sdsi", $incomeDescription, $principal_amount, $start_date, $incomeId);
                $updInc->execute();
                $updInc->close();
                
                // 4. Update Reports
                // Recalculate for Old Month (if changed)
                if (date('Y-m', strtotime($oldDate)) != date('Y-m', strtotime($start_date))) {
                    recalculateReport($conn, date('Y-m-d', strtotime($oldDate)));
                }
                // Recalculate for New Month
                recalculateReport($conn, $start_date);
            }

            echo json_encode(['success' => true, 'message' => 'Loan updated successfully']);
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
    // This is a simplified report recalculation for Income mostly
    $month = strtoupper(date('M-Y', strtotime($date)));
    $firstDay = date('Y-m-01', strtotime($date));
    $lastDay = date('Y-m-t', strtotime($date));
    
    // Get total income
    $incQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $incResult = $conn->query($incQuery);
    $totalIncome = $incResult->fetch_assoc()['total'];
    
    // Get report to update
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
