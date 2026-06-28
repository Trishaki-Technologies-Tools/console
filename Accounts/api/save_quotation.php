<?php
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$clientName = $data['clientName'] ?? '';
$phone = $data['phone'] ?? '';
$email = $data['email'] ?? '';
$gstNumber = $data['gstNumber'] ?? '';
$date = $data['date'] ?? date('Y-m-d');
$items = $data['items'] ?? [];
$totalAmount = floatval($data['totalAmount'] ?? 0);
$status = $data['status'] ?? 'sent';
$quotationNo = $data['quotationNo'] ?? null;

try {
    $conn->begin_transaction();

    // 1. Sync Client
    if (empty($phone)) {
        throw new Exception("Client phone number is required");
    }

    $address = $data['address'] ?? '';

    $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $clientId = $result->fetch_assoc()['id'];
        $stmt2 = $conn->prepare("UPDATE clients SET name = ?, email = ?, gst_number = ?, address = ? WHERE id = ?");
        $stmt2->bind_param("ssssi", $clientName, $email, $gstNumber, $address, $clientId);
        $stmt2->execute();
    } else {
        $stmt2 = $conn->prepare("INSERT INTO clients (name, phone, email, gst_number, address) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("sssss", $clientName, $phone, $email, $gstNumber, $address);
        $stmt2->execute();
        $clientId = $conn->insert_id;
    }

    $itemsJson = json_encode($items);

    if ($quotationNo) {
        // Edit mode
        $stmt = $conn->prepare("UPDATE quotations SET client_id = ?, items = ?, total_amount = ?, quotation_date = ?, status = ? WHERE quotation_no = ?");
        $stmt->bind_param("isdsss", $clientId, $itemsJson, $totalAmount, $date, $status, $quotationNo);
        $stmt->execute();

        // Get ID of edited quotation
        $stmt_id = $conn->prepare("SELECT id FROM quotations WHERE quotation_no = ?");
        $stmt_id->bind_param("s", $quotationNo);
        $stmt_id->execute();
        $id = $stmt_id->get_result()->fetch_assoc()['id'];
        $stmt_id->close();
    } else {
        // Create mode
        $year = date('Y');
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM quotations WHERE quotation_no LIKE ?");
        $pattern = "QT-$year-%";
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $nextNumber = $row['count'] + 1;
        $quotationNo = 'QT-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO quotations (quotation_no, client_id, items, total_amount, quotation_date, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisdss", $quotationNo, $clientId, $itemsJson, $totalAmount, $date, $status);
        $stmt->execute();
        $id = $conn->insert_id;
    }

    $conn->commit();
    echo json_encode(['success' => true, 'quotationNo' => $quotationNo, 'id' => $id]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
