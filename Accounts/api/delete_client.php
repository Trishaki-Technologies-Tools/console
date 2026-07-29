<?php
header('Content-Type: application/json');
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $conn->begin_transaction();
        
        // Fetch client name first
        $nameStmt = $conn->prepare("SELECT company_name FROM clients WHERE id = ?");
        $nameStmt->bind_param("i", $id);
        $nameStmt->execute();
        $clientRes = $nameStmt->get_result()->fetch_assoc();
        $nameStmt->close();
        $clientName = $clientRes ? $clientRes['company_name'] : 'Unknown';

        // 1. Delete associated invoices
        $stmt1 = $conn->prepare("DELETE FROM invoices WHERE client_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // 2. Delete associated quotations
        $stmt2 = $conn->prepare("DELETE FROM quotations WHERE client_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();
        
        // 3. Delete client
        $stmt3 = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $stmt3->bind_param("i", $id);
        
        if ($stmt3->execute()) {
            log_action($conn, 'DELETE', 'clients', $id, "Deleted client: $clientName (ID: $id)");
            $conn->commit();
            echo json_encode(['success' => true]);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $stmt3->error]);
        }
        $stmt3->close();
    } catch (Exception $e) {
        if ($conn) $conn->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing client ID']);
}
?>
