<?php
header('Content-Type: application/json');

// Custom Error Handler to return JSON
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "PHP Error: $errstr", 'details' => "File: $errfile Line: $errline"]);
    exit;
}
set_error_handler("jsonErrorHandler");

try {
    require_once 'config.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $month = $input['month'] ?? '';

        if (empty($month)) {
             throw new Exception("Month required");
        }
        
        $conn->begin_transaction();

        try {
            // 1. Delete Report
            $stmt = $conn->prepare("DELETE FROM reports WHERE month = ?");
            $stmt->bind_param("s", $month);
            $stmt->execute();
            $stmt->close();

            // 2. Parse Month to Date Range for Incomes/Expenses
            // Format: MMM-YYYY (e.g. FEB-2026) -> 2026-02-01 to 2026-02-28
            $dateObj = DateTime::createFromFormat('M-Y|', $month);
            if (!$dateObj) {
                throw new Exception("Invalid month format");
            }
            
            $startDate = $dateObj->format('Y-m-01');
            $endDate = $dateObj->format('Y-m-t');

            // 3. Delete Incomes
            $stmtInc = $conn->prepare("DELETE FROM incomes WHERE date BETWEEN ? AND ?");
            $stmtInc->bind_param("ss", $startDate, $endDate);
            $stmtInc->execute();
            $stmtInc->close();

            // 4. Delete Expenses
            $stmtExp = $conn->prepare("DELETE FROM expenses WHERE date BETWEEN ? AND ?");
            $stmtExp->bind_param("ss", $startDate, $endDate);
            $stmtExp->execute();
            $stmtExp->close();

            $conn->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }

    if (isset($conn)) $conn->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
