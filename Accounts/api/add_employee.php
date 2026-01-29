<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $role = $conn->real_escape_string($_POST['role']);

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Name is required']);
        exit;
    }

    $sql = "INSERT INTO employees (name, role) VALUES ('$name', '$role')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        // Check for duplicate entry
        if ($conn->errno == 1062) {
             echo json_encode(['success' => false, 'error' => 'Employee already exists']);
        } else {
             echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    }
}
$conn->close();
?>
