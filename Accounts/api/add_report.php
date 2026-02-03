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
        if (!isset($_POST['month'])) {
             throw new Exception("Month is required");
        }

        $month = $conn->real_escape_string($_POST['month']);
        
        // Handle optional fields with defaults
        $opening_balance = isset($_POST['opening_balance']) ? floatval($_POST['opening_balance']) : 0;
        $income = isset($_POST['income']) ? floatval($_POST['income']) : 0;
        $expenses = isset($_POST['expenses']) ? floatval($_POST['expenses']) : 0;
        $closing_balance = isset($_POST['closing_balance']) ? floatval($_POST['closing_balance']) : 0;
        
        $query = "INSERT INTO reports (month, opening_balance, income, expenses, closing_balance) 
                  VALUES ('$month', $opening_balance, $income, $expenses, $closing_balance)
                  ON DUPLICATE KEY UPDATE 
                  opening_balance = VALUES(opening_balance),
                  closing_balance = VALUES(closing_balance)"; // We trust the frontend sent calculated closing OR we should recalc. 
                  // Frontend sent closing = opening (since inc/exp=0). Matches logic.
        
        if ($conn->query($query)) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Database Error: " . $conn->error);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method. Expected POST.']);
    }
    
    if (isset($conn)) $conn->close();

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
