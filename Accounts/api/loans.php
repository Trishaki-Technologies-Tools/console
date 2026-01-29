<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Check if table exists first to avoid 500 error log
    $checkTable = $conn->query("SHOW TABLES LIKE 'loans'");
    if($checkTable->num_rows == 0) {
        echo json_encode([]); // Return empty array if table doesn't exist yet
        exit;
    }

    $sql = "SELECT * FROM loans ORDER BY created_at DESC";
    $result = $conn->query($sql);

    $loans = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $loans[] = $row;
        }
    }

    echo json_encode($loans);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
