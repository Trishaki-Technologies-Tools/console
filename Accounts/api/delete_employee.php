<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Soft delete by setting status to 'Inactive' or just delete?
    // User requested "manage employee... add...". Usually delete is expected.
    // Let's do a hard delete for simplicity as requested in similar tasks, or soft delete if we want to keep history integrity.
    // Given the simple nature, I'll do a hard delete but check if used in salary_logs?
    // Actually, salary_logs stores name string, so deleting employee from list won't break logs.
    
    $sql = "DELETE FROM employees WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}
$conn->close();
?>
