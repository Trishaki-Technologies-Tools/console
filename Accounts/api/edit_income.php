<?php
header('Content-Type: application/json');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $description = $conn->real_escape_string($_POST['description']);
    $amount = floatval($_POST['amount']);
    $category = $conn->real_escape_string($_POST['category']);
    $payment_mode = $conn->real_escape_string($_POST['payment_mode']);
    $date = $conn->real_escape_string($_POST['date']); 

    // Get old date to handle month changes
    $oldQuery = "SELECT date FROM incomes WHERE id = $id";
    $oldResult = $conn->query($oldQuery);
    if ($oldResult && $oldResult->num_rows > 0) {
        $oldDate = $oldResult->fetch_assoc()['date'];
        
        $query = "UPDATE incomes SET description='$description', amount=$amount, date='$date', category='$category', payment_mode='$payment_mode' WHERE id=$id";
        
        if ($conn->query($query)) {
            // Update reports for both old and new months
            $monthsToUpdate = [
                strtoupper(date('M-Y', strtotime($oldDate))),
                strtoupper(date('M-Y', strtotime($date)))
            ];
            $monthsToUpdate = array_unique($monthsToUpdate);

            foreach ($monthsToUpdate as $monthStr) {
                 // Calculate month range
                 $parts = explode('-', $monthStr);
                 if (count($parts) === 2) {
                     $monthName = $parts[0];
                     $year = $parts[1];
                     $firstDay = date('Y-m-01', strtotime("1 $monthName $year"));
                     $lastDay = date('Y-m-t', strtotime("1 $monthName $year"));

                     // Recalculate income
                     $incomeQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM incomes WHERE date >= '$firstDay' AND date <= '$lastDay'";
                     $incomeResult = $conn->query($incomeQuery);
                     $totalIncome = $incomeResult->fetch_assoc()['total'];

                     // Get report
                     $reportQuery = "SELECT opening_balance, expenses FROM reports WHERE month = '$monthStr'";
                     $reportResult = $conn->query($reportQuery);

                     if ($reportResult && $reportResult->num_rows > 0) {
                         $report = $reportResult->fetch_assoc();
                         $closingBalance = $report['opening_balance'] + $totalIncome - $report['expenses'];
                         $conn->query("UPDATE reports SET income = $totalIncome, closing_balance = $closingBalance WHERE month = '$monthStr'");
                     }
                 }
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Record not found']);
    }
}
$conn->close();
?>
