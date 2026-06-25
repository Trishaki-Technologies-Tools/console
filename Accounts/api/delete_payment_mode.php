<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Check usage in incomes
        $incomeUsage = "SELECT COUNT(*) as count FROM incomes WHERE payment_mode_id = $id";
        $incomeResult = $conn->query($incomeUsage);
        $incomeCount = $incomeResult ? $incomeResult->fetch_assoc()['count'] : 0;
        
        // Check usage in expenses
        $expenseUsage = "SELECT COUNT(*) as count FROM expenses WHERE payment_mode_id = $id";
        $expenseResult = $conn->query($expenseUsage);
        $expenseCount = $expenseResult ? $expenseResult->fetch_assoc()['count'] : 0;
        
        if ($incomeCount > 0 || $expenseCount > 0) {
            echo json_encode(['success' => false, 'error' => 'Cannot delete payment mode that is in use by transactions']);
        } else {
            $query = "DELETE FROM payment_modes WHERE id = $id";
            
            if ($conn->query($query)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => $conn->error]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No ID provided']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
