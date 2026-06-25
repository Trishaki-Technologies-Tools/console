<?php
header('Content-Type: application/json');
require_once 'config.php';

function getCategoryId($conn, $name) {
    $stmt = $conn->prepare("SELECT id FROM incomes_categories WHERE category_name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id'];
    } else {
        $stmt2 = $conn->prepare("INSERT INTO incomes_categories (category_name) VALUES (?)");
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
    $creditor_name   = $_POST['creditor_name'] ?? '';
    $principal_amount = floatval($_POST['principal_amount'] ?? 0);
    $charges         = floatval($_POST['charges'] ?? 0);
    $interest_rate   = floatval($_POST['interest_rate'] ?? 0);
    $start_date      = $_POST['start_date'] ?? date('Y-m-d');
    $payment_mode    = $_POST['payment_mode'] ?? 'Cash';
    $description     = $_POST['description'] ?? '';
    $status          = 'active';

    $net_amount = $principal_amount - $charges;

    try {
        if (empty($creditor_name) || empty($principal_amount)) {
            throw new Exception("Missing required fields");
        }

        $conn->begin_transaction();

        // 1. Sync with Incomes Table (net amount received)
        $incomeDescription = "Loan from " . $creditor_name . ($description ? " - $description" : "");
        $category_id = getCategoryId($conn, 'Loan');
        $payment_mode_id = getPaymentModeId($conn, $payment_mode);

        $incStmt = $conn->prepare("INSERT INTO incomes (description, amount, date, category_id, payment_mode_id) VALUES (?, ?, ?, ?, ?)");
        if (!$incStmt) throw new Exception("Income prepare failed: " . $conn->error);
        $incStmt->bind_param("sdsii", $incomeDescription, $net_amount, $start_date, $category_id, $payment_mode_id);

        if ($incStmt->execute()) {
            $incomeId = $conn->insert_id;
            $incStmt->close();

            $tStmt = $conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('income', ?, ?, ?, 'incomes', ?)");
            $tStmt->bind_param("dsis", $net_amount, $start_date, $incomeId, $incomeDescription);
            $tStmt->execute();

            $incomeMonth = strtoupper(date('M-Y', strtotime($start_date)));
            recalculateReport($conn, $incomeMonth);
        } else {
            $incError = $incStmt->error;
            $incStmt->close();
            throw new Exception("Income insert failed: " . $incError);
        }

        // 2. Insert Loan (store annual interest rate)
        $loanDesc = $description ?: $creditor_name;
        $stmt = $conn->prepare("INSERT INTO loans (description, principal_amount, interest_rate, duration_months, start_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $duration_months = 12;
        $stmt->bind_param("sddiss", $creditor_name, $principal_amount, $interest_rate, $duration_months, $start_date, $status);

        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Loan added successfully']);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

    } catch (Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

function recalculateReport($conn, $month) {
    $firstDay = date('Y-m-01', strtotime("01-$month")); 
    $lastDay = date('Y-m-t', strtotime("01-$month"));
    
    $incQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date >= '$firstDay' AND date <= '$lastDay'";
    $incResult = $conn->query($incQuery);
    $totalIncome = $incResult->fetch_assoc()['total'];

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
