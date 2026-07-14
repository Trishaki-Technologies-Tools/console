<?php
header('Content-Type: application/json');
require_once 'config.php';

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
    $description = $_POST['description'];
    $amount = floatval($_POST['amount']);
    $category_name = $_POST['category'] ?? 'Other';
    $payment_mode_name = $_POST['payment_mode'] ?? 'Cash';
    $date = $_POST['date'] ?? date('Y-m-d');
    $date_db = date('Y-m-d', strtotime($date));
    
    // File upload
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['attachment']['tmp_name'];
        $file_name = $_FILES['attachment']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('attach_', true) . '.' . $file_ext;
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
            $attachment_path = 'uploads/' . $new_file_name;
        }
    }
    
    $category_id = getCategoryId($conn, $category_name);
    $payment_mode_id = getPaymentModeId($conn, $payment_mode_name);
    
    $stmt = $conn->prepare("INSERT INTO expenses (description, amount, date, category_id, payment_mode_id, attachment) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsiis", $description, $amount, $date_db, $category_id, $payment_mode_id, $attachment_path);
    
    if ($stmt->execute()) {
        $expense_id = $conn->insert_id;
        
        // Log to transactions table
        $tStmt = $conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('expense', ?, ?, ?, 'expenses', ?)");
        $tStmt->bind_param("dsis", $amount, $date, $expense_id, $description);
        $tStmt->execute();
        
        // Update the month's report based on the expense date
        $expenseMonth = strtoupper(date('M-Y', strtotime($date)));
        $firstDay = date('Y-m-01', strtotime($date));
        $lastDay = date('Y-m-t', strtotime($date));
        
        // Recalculate expenses for that month
        $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                         WHERE date >= '$firstDay' AND date <= '$lastDay'";
        $expenseResult = $conn->query($expenseQuery);
        $totalExpenses = $expenseResult->fetch_assoc()['total'];
        
        // Get opening balance and income
        $reportQuery = "SELECT opening_balance, income FROM reports WHERE month = '$expenseMonth'";
        $reportResult = $conn->query($reportQuery);
        
        if ($reportResult && $reportResult->num_rows > 0) {
            $report = $reportResult->fetch_assoc();
            $closingBalance = $report['opening_balance'] + $report['income'] - $totalExpenses;
            
            // Update report
            $updateQuery = "UPDATE reports SET expenses = $totalExpenses, closing_balance = $closingBalance 
                           WHERE month = '$expenseMonth'";
            $conn->query($updateQuery);
        }
        
        echo json_encode([
            'success' => true,
            'expense_id' => $expense_id,
            'token' => encryptToken("EXP-" . $expense_id)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
