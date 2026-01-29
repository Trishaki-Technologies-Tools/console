<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $conn->real_escape_string($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = $conn->real_escape_string($_POST['category']);
    $payment_mode = $conn->real_escape_string($_POST['payment_mode']);
    $date = $conn->real_escape_string($_POST['date']); // Get date from form
    
    $query = "INSERT INTO incomes (description, amount, date, category, payment_mode) 
              VALUES ('$description', $amount, '$date', '$category', '$payment_mode')";
    
    if ($conn->query($query)) {
        // Update the month's report based on the income date
        $incomeMonth = strtoupper(date('M-Y', strtotime($date)));
        $firstDay = date('Y-m-01', strtotime($date));
        $lastDay = date('Y-m-t', strtotime($date));
        
        // Recalculate income for that month
        $incomeQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes 
                        WHERE date >= '$firstDay' AND date <= '$lastDay'";
        $incomeResult = $conn->query($incomeQuery);
        $totalIncome = $incomeResult->fetch_assoc()['total'];
        
        // Get opening balance and expenses
        $reportQuery = "SELECT opening_balance, expenses FROM reports WHERE month = '$incomeMonth'";
        $reportResult = $conn->query($reportQuery);
        
        if ($reportResult && $reportResult->num_rows > 0) {
            $report = $reportResult->fetch_assoc();
            $closingBalance = $report['opening_balance'] + $totalIncome - $report['expenses'];
            
            // Update report
            $updateQuery = "UPDATE reports SET income = $totalIncome, closing_balance = $closingBalance 
                           WHERE month = '$incomeMonth'";
            $conn->query($updateQuery);
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
