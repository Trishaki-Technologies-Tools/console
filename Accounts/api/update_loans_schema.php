<?php
require_once 'config.php';

// Add columns to loans table
$columns = [
    "interest_type ENUM('Monthly', 'Annual') DEFAULT 'Monthly'",
    "last_interest_payment_date DATE DEFAULT NULL",
    "income_id INT DEFAULT NULL"
];

foreach ($columns as $colDef) {
    // Extract column name for check
    $parts = explode(' ', $colDef);
    $colName = $parts[0];
    
    $check = "SHOW COLUMNS FROM loans LIKE '$colName'";
    $result = $conn->query($check);
    
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE loans ADD COLUMN $colDef";
        if ($conn->query($sql) === TRUE) {
            echo "Column '$colName' added successfully.<br>";
        } else {
            echo "Error adding column '$colName': " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$colName' already exists.<br>";
    }
}

$conn->close();
?>
