<?php
require_once 'config.php';

// Add paid_amount to loans table
$column = "paid_amount DECIMAL(10, 2) DEFAULT 0.00";
$table = "loans";

$check = "SHOW COLUMNS FROM $table LIKE 'paid_amount'";
$result = $conn->query($check);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE $table ADD COLUMN $column";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'paid_amount' added successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'paid_amount' already exists";
}

$conn->close();
?>
