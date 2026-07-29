<?php
header('Content-Type: application/json');
require_once 'config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$type = $_GET['type'] ?? $_POST['type'] ?? ''; // 'colleges' or 'departments'

if (!in_array($type, ['colleges', 'departments'])) {
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

if ($action === 'get') {
    try {
        $result = $conn->query("SELECT * FROM $type ORDER BY name ASC");
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode($items);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} 
elseif ($action === 'add') {
    $name = $_POST['name'] ?? '';
    if (empty($name)) {
        echo json_encode(['error' => 'Name is required']);
        exit;
    }
    try {
        $stmt = $conn->prepare("INSERT INTO $type (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
        } else {
            echo json_encode(['error' => 'Failed to add']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
elseif ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    try {
        $stmt = $conn->prepare("DELETE FROM $type WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to delete']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
