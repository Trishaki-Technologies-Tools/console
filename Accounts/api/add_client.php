<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $gst_number = $_POST['gst_number'] ?? '';
    $address = $_POST['address'] ?? '';

    $client_type = $_POST['client_type'] ?? 'Client';
    $college_name = $_POST['college_name'] ?? '';
    $department = $_POST['department'] ?? '';

    if (empty($name)) {
        echo json_encode(['error' => 'Name is required']);
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO clients (name, phone, email, gst_number, address, client_type, college_name, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $phone, $email, $gst_number, $address, $client_type, $college_name, $department);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['error' => 'Failed to add client']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
