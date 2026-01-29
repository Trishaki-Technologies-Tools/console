<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creditor_name = $_POST['creditor_name'] ?? '';
    $principal_amount = $_POST['principal_amount'] ?? 0;
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $description = $_POST['description'] ?? '';
    $status = 'Active';

    $interest_type = $_POST['interest_type'] ?? 'Monthly'; // Default to Monthly

    try {
        if (empty($creditor_name) || empty($principal_amount)) {
            throw new Exception("Missing required fields");
        }

        // --- Sync with Incomes Table ---
        $incomeDescription = "Loan from " . $creditor_name;
        $incomeCategory = "Loan"; // Changed from "Other"
        $incomePaymentMode = "Cash"; // Default or add field

        $incStmt = $conn->prepare("INSERT INTO incomes (description, amount, date, category, payment_mode) VALUES (?, ?, ?, ?, ?)");
        if (!$incStmt) {
             throw new Exception("Income prepare failed: " . $conn->error);
        }
        $incStmt->bind_param("sdsss", $incomeDescription, $principal_amount, $start_date, $incomeCategory, $incomePaymentMode);
        
        if ($incStmt->execute()) {
             $incomeId = $conn->insert_id;
             $incStmt->close();

             // Update Reports for Income
             $incomeMonth = strtoupper(date('M-Y', strtotime($start_date)));
             recalculateReport($conn, $incomeMonth);

        } else {
             $incError = $incStmt->error;
             $incStmt->close();
             throw new Exception("Income insert failed: " . $incError);
        }

        // --- Insert Loan ---
        $stmt = $conn->prepare("INSERT INTO loans (creditor_name, principal_amount, interest_rate, interest_type, start_date, description, status, income_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sdsssssi", $creditor_name, $principal_amount, $interest_rate, $interest_type, $start_date, $description, $status, $incomeId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Loan added and synced to income successfully']);
        } else {
             throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

function recalculateReport($conn, $month) {
    // Get opening balance and previous report logic...
    // For simplicity, let's just make sure we update the income column for that month
    $query = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date_format(date, '%b-%Y') = '$month'";
    // Date format in SQL might be tricky with Month names like 'JAN-2026', let's use PHP ranges
    // ... Actually easier to just update income = income + new amount if record exists, or insert.
    // NOTE: reports table structure logic is a bit complex to fully replicate here without duplication.
    // Let's implement a simplified update that replicates add_income.php logic if possible or just update the specific month.
    
    // RE-USE LOGIC roughly:
    // 1. Get total income for the month
    $firstDay = date('Y-m-01', strtotime("01-$month")); 
    $lastDay = date('Y-m-t', strtotime("01-$month"));
    
    $incQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $incResult = $conn->query($incQuery);
    $totalIncome = $incResult->fetch_assoc()['total'];

    // 2. Get report
    $repQuery = "SELECT * FROM reports WHERE month = '$month'";
    $repResult = $conn->query($repQuery);
    
    if ($repResult->num_rows > 0) {
        $rep = $repResult->fetch_assoc();
        $closingBalance = $rep['opening_balance'] + $totalIncome - $rep['expenses'];
        $conn->query("UPDATE reports SET income = $totalIncome, closing_balance = $closingBalance WHERE month = '$month'");
    }
}

$conn->close();
?>
