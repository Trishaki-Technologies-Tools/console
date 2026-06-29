<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit;
    }

    // Check if employee already exists
    $checkStmt = $conn->prepare("SELECT id FROM employees WHERE name = ?");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'Employee already exists']);
        $checkStmt->close();
        $conn->close();
        exit;
    }
    $checkStmt->close();

    // Insert new employee — detect available columns first
    $eCols = [];
    $cr = $conn->query("SHOW COLUMNS FROM employees");
    while ($c = $cr->fetch_assoc()) $eCols[] = $c['Field'];

    if (in_array('designation', $eCols) && in_array('status', $eCols)) {
        $stmt = $conn->prepare("INSERT INTO employees (name, designation, status) VALUES (?, '', 'active')");
    } elseif (in_array('designation', $eCols)) {
        $stmt = $conn->prepare("INSERT INTO employees (name, designation) VALUES (?, '')");
    } elseif (in_array('status', $eCols)) {
        $stmt = $conn->prepare("INSERT INTO employees (name, status) VALUES (?, 'Active')");
    } else {
        $stmt = $conn->prepare("INSERT INTO employees (name) VALUES (?)");
    }
    $stmt->bind_param("s", $name);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
