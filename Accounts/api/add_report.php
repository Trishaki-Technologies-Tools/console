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
                    // Get or create "Opening Balance" category
                    $catRow = $conn->query("SELECT id FROM incomes_categories WHERE category_name = 'Opening Balance'")->fetch_assoc();
                    if (!$catRow) {
                        $conn->query("INSERT INTO incomes_categories (category_name) VALUES ('Opening Balance')");
                        $catId = $conn->insert_id;
                    } else {
                        $catId = $catRow['id'];
                    }

                    // Get payment mode id
                    $pmRow = $conn->query("SELECT id FROM payment_modes WHERE mode_name = '$payment_mode' LIMIT 1")->fetch_assoc();
                    $pmId = $pmRow ? $pmRow['id'] : 1;

                    // Delete existing Opening Balance for this month
                    $delSql = "DELETE FROM incomes WHERE category_id = $catId AND DATE_FORMAT(date, '%b-%Y') = '$month'";
                    $conn->query($delSql);

                    $desc = "Opening Balance for " . $month;
                    
                    $insSql = "INSERT INTO incomes (date, description, category_id, payment_mode_id, amount) 
                               VALUES ('$date', '$desc', $catId, $pmId, $opening_balance)";
                    
                    if (!$conn->query($insSql)) {
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
