<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $query = "
        SELECT 
            a.id,
            a.action,
            a.table_name,
            a.record_id,
            a.details,
            a.created_at,
            COALESCE(u.username, 'System') as username
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 200
    ";
    
    $result = $conn->query($query);
    $logs = [];
    
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    echo json_encode($logs);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
