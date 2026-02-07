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
        $payment_mode = isset($_POST['payment_mode']) ? $conn->real_escape_string($_POST['payment_mode']) : 'HDFC Bank';
        $income = isset($_POST['income']) ? floatval($_POST['income']) : 0;
        $expenses = isset($_POST['expenses']) ? floatval($_POST['expenses']) : 0;
        $closing_balance = isset($_POST['closing_balance']) ? floatval($_POST['closing_balance']) : 0;
        
        $query = "INSERT INTO reports (month, opening_balance, income, expenses, closing_balance) 
                  VALUES ('$month', $opening_balance, $income, $expenses, $closing_balance)
                  ON DUPLICATE KEY UPDATE 
                  opening_balance = VALUES(opening_balance),
                  closing_balance = VALUES(closing_balance)"; 
        
        if ($conn->query($query)) {

            // If Opening Balance > 0, record it as a transaction so it reflects in Bank Balance stats
            if ($opening_balance > 0) {
                // Parse Month (e.g. JAN-2025) to Date (2025-01-01)
                $dateObj = DateTime::createFromFormat('M-Y', $month);
                if ($dateObj) {
                    $date = $dateObj->format('Y-m-01');
                    
                    // Check if Opening Balance already exists for this month to avoid duplicates (simplified check)
                    // Or usually Opening Balance is a one-time thing per report. 
                    // We'll delete any existing 'Opening Balance' for this month to be safe/idempotent
                    $delSql = "DELETE FROM incomes WHERE category = 'Opening Balance' AND DATE_FORMAT(date, '%b-%Y') = '$month'";
                    $conn->query($delSql);

                    $desc = "Opening Balance for " . $month;
                    $cat = "Opening Balance";
                    
                    $insSql = "INSERT INTO incomes (date, description, category, payment_mode, amount) 
                               VALUES ('$date', '$desc', '$cat', '$payment_mode', $opening_balance)";
                    
                    if (!$conn->query($insSql)) {
                        // Log error but don't fail the whole request? Or fail?
                        // Let's just log it. 
                        error_log("Failed to insert Opening Balance transaction: " . $conn->error);
                    }
                }
            }

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
