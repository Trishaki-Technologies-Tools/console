<?php
header('Content-Type: application/json');
require_once 'config.php';

// This script fixes opening balances to match previous month's closing balance

// Get all reports ordered by date
$query = "SELECT * FROM reports ORDER BY 
          STR_TO_DATE(CONCAT('01-', month), '%d-%b-%Y') ASC";
$result = $conn->query($query);

$reports = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

$fixed = 0;
$previousClosing = null;

foreach ($reports as $index => $report) {
    if ($index > 0 && $previousClosing !== null) {
        // Update opening balance to match previous month's closing balance
        if ($report['opening_balance'] != $previousClosing) {
            $newClosing = $previousClosing + $report['income'] - $report['expenses'];
            
            $updateQuery = "UPDATE reports 
                           SET opening_balance = $previousClosing, 
                               closing_balance = $newClosing 
                           WHERE id = " . $report['id'];
            
            if ($conn->query($updateQuery)) {
                $fixed++;
            }
            
            $previousClosing = $newClosing;
        } else {
            $previousClosing = $report['closing_balance'];
        }
    } else {
        $previousClosing = $report['closing_balance'];
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Fixed $fixed report(s)",
    'fixed_count' => $fixed
]);

$conn->close();
?>
