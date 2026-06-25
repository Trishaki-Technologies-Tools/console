<?php
header('Content-Type: application/json');
require_once 'config.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$payee = $data['payee'] ?? '';
$amount = $data['amount'] ?? 0;
$mode = $data['mode'] ?? 'Cash';
$date = $data['date'] ?? date('Y-m-d');
$description = $data['description'] ?? '';

try {
    // Generate voucher number
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM vouchers WHERE voucher_no LIKE ?");
    $pattern = "PV-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nextNumber = $row['count'] + 1;
    $refNo = 'PV-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    // Insert voucher matching 20-table schema
    $stmt = $conn->prepare("INSERT INTO vouchers (voucher_no, payee_name, amount, payment_mode, date, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsss", $refNo, $payee, $amount, $mode, $date, $description);
    $stmt->execute();
    
    // Log transaction
    $vId = $conn->insert_id;
    $tStmt = $conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('expense', ?, ?, ?, 'vouchers', ?)");
    $tDesc = "Voucher " . $refNo . " to " . $payee;
    $tStmt->bind_param("dsis", $amount, $date, $vId, $tDesc);
    $tStmt->execute();

    echo json_encode([
        'success' => true,
        'refNo' => $refNo
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
