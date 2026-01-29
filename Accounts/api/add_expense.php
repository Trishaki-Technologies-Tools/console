<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = $conn->real_escape_string($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = $conn->real_escape_string($_POST['category']);
    $payment_mode = $conn->real_escape_string($_POST['payment_mode']);
    $date = $conn->real_escape_string($_POST['date']); // Get date from form
    
    $query = "INSERT INTO expenses (description, amount, date, category, payment_mode) 
              VALUES ('$description', $amount, '$date', '$category', '$payment_mode')";
    
    if ($conn->query($query)) {
        // Update the month's report based on the expense date
        $expenseMonth = strtoupper(date('M-Y', strtotime($date)));
        $firstDay = date('Y-m-01', strtotime($date));
        $lastDay = date('Y-m-t', strtotime($date));
        
        // Recalculate expenses for that month
        $expenseQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                         WHERE date >= '$firstDay' AND date <= '$lastDay'";
        $expenseResult = $conn->query($expenseQuery);
        $totalExpenses = $expenseResult->fetch_assoc()['total'];
        
        // Get opening balance and income
        $reportQuery = "SELECT opening_balance, income FROM reports WHERE month = '$expenseMonth'";
        $reportResult = $conn->query($reportQuery);
        
        if ($reportResult && $reportResult->num_rows > 0) {
            $report = $reportResult->fetch_assoc();
            $closingBalance = $report['opening_balance'] + $report['income'] - $totalExpenses;
            
            // Update report
            $updateQuery = "UPDATE reports SET expenses = $totalExpenses, closing_balance = $closingBalance 
                           WHERE month = '$expenseMonth'";
            $conn->query($updateQuery);
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
}

$conn->close();
?>
