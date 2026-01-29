<?php
require_once 'config.php';

// Tables to update
$tables = ['incomes', 'expenses', 'salary_logs'];

// Modes to convert to 'HDFC Bank'
$modesToConvert = ['UPI', 'Bank Transfer', 'Card', 'Online'];
$modeList = "'" . implode("','", $modesToConvert) . "'";

foreach ($tables as $table) {
    echo "Updating $table...<br>";
    $sql = "UPDATE $table SET payment_mode = 'HDFC Bank' WHERE payment_mode IN ($modeList)";
    
    if ($conn->query($sql) === TRUE) {
        echo "Updated " . $conn->affected_rows . " records in $table<br>";
    } else {
        echo "Error updating $table: " . $conn->error . "<br>";
    }
}
$conn->close();
?>
