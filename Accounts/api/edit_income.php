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
    $id = intval($_POST['id']);
    $description = $_POST['description'];
    $amount = floatval($_POST['amount']);
    $category_name = $_POST['category'] ?? 'Other';
    $payment_mode_name = $_POST['payment_mode'] ?? 'Cash';
    $date = $_POST['date'] ?? date('Y-m-d');

    // Get old date and attachment to handle month changes and file deletion
    $oldQuery = "SELECT date, attachment FROM incomes WHERE id = $id";
    $oldResult = $conn->query($oldQuery);
    if ($oldResult && $oldResult->num_rows > 0) {
        $oldRow = $oldResult->fetch_assoc();
        $oldDate = $oldRow['date'];
        $oldAttachment = $oldRow['attachment'];
        
        $date_db = date('Y-m-d', strtotime($date));
        
        // File upload
        $attachment_path = null;
        $has_new_attachment = false;
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
                $has_new_attachment = true;
            }
        }

        $category_id = getCategoryId($conn, $category_name);
        $payment_mode_id = getPaymentModeId($conn, $payment_mode_name);
        
        if ($has_new_attachment) {
            $stmt = $conn->prepare("UPDATE incomes SET description = ?, amount = ?, date = ?, category_id = ?, payment_mode_id = ?, attachment = ? WHERE id = ?");
            $stmt->bind_param("sdsiisi", $description, $amount, $date_db, $category_id, $payment_mode_id, $attachment_path, $id);
        } else {
            $stmt = $conn->prepare("UPDATE incomes SET description = ?, amount = ?, date = ?, category_id = ?, payment_mode_id = ? WHERE id = ?");
            $stmt->bind_param("sdsiii", $description, $amount, $date_db, $category_id, $payment_mode_id, $id);
        }
        
        if ($stmt->execute()) {
            // Delete old attachment if a new one was uploaded
            if ($has_new_attachment && $oldAttachment && file_exists('../' . $oldAttachment)) {
                unlink('../' . $oldAttachment);
            }
            // Update transaction log
            $tStmt = $conn->prepare("UPDATE transactions SET amount = ?, date = ?, description = ? WHERE reference_id = ? AND reference_table = 'incomes'");
            $tStmt->bind_param("dssi", $amount, $date, $description, $id);
            $tStmt->execute();
            
            // Update reports for both old and new months
            $monthsToUpdate = [
                strtoupper(date('M-Y', strtotime($oldDate))),
                strtoupper(date('M-Y', strtotime($date)))
            ];
            $monthsToUpdate = array_unique($monthsToUpdate);

            foreach ($monthsToUpdate as $monthStr) {
                 // Calculate month range
                 $parts = explode('-', $monthStr);
                 if (count($parts) === 2) {
                     $monthName = $parts[0];
                     $year = $parts[1];
                     $firstDay = date('Y-m-01', strtotime("1 $monthName $year"));
                     $lastDay = date('Y-m-t', strtotime("1 $monthName $year"));

                     // Recalculate income
                     $incomeQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date >= '$firstDay' AND date <= '$lastDay'";
                     $incomeResult = $conn->query($incomeQuery);
                     $totalIncome = $incomeResult->fetch_assoc()['total'];

                     // Get report
                     $reportQuery = "SELECT opening_balance, expenses FROM reports WHERE month = '$monthStr'";
                     $reportResult = $conn->query($reportQuery);

                     if ($reportResult && $reportResult->num_rows > 0) {
                          $report = $reportResult->fetch_assoc();
                          $closingBalance = $report['opening_balance'] + $totalIncome - $report['expenses'];
                          $conn->query("UPDATE reports SET income = $totalIncome, closing_balance = $closingBalance WHERE month = '$monthStr'");
                     }
                 }
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Record not found']);
    }
}
$conn->close();
?>
