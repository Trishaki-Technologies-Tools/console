<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (strtoupper($email) === 'N/A' || strtoupper($email) === 'NOT APPLICABLE') {
        $email = '';
    }

    $gst_number = trim($_POST['gst_number'] ?? '');
    if (strtoupper($gst_number) === 'N/A' || strtoupper($gst_number) === 'NOT APPLICABLE') {
        $gst_number = '';
    }

    $address = $_POST['address'] ?? '';

    $client_type = $_POST['client_type'] ?? 'Client';
    $college_name = $_POST['college_name'] ?? '';
    $department = $_POST['department'] ?? '';

    if (empty($name)) {
        echo json_encode(['error' => 'Name is required']);
        exit;
    }

    try {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE clients SET name = ?, phone = ?, email = ?, gst_number = ?, address = ?, client_type = ?, college_name = ?, department = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $name, $phone, $email, $gst_number, $address, $client_type, $college_name, $department, $id);
            if ($stmt->execute()) {
                log_action($conn, 'UPDATE', 'clients', $id, "Updated client: $name");
                echo json_encode(['success' => true, 'id' => $id]);
            } else {
                echo json_encode(['error' => 'Failed to update client']);
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO clients (name, phone, email, gst_number, address, client_type, college_name, department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $name, $phone, $email, $gst_number, $address, $client_type, $college_name, $department);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                log_action($conn, 'CREATE', 'clients', $newId, "Added client: $name");
                echo json_encode(['success' => true, 'id' => $newId]);
            } else {
                echo json_encode(['error' => 'Failed to add client']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>