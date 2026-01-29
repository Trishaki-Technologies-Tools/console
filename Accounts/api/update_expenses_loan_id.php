<?php
require_once 'config.php';

// Add loan_id to expenses table
$column = "loan_id INT DEFAULT NULL";
$table = "expenses";

$check = "SHOW COLUMNS FROM $table LIKE 'loan_id'";
$result = $conn->query($check);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE $table ADD COLUMN $column";
    if ($conn->query($sql) === TRUE) {
        echo "Column 'loan_id' added to expenses successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'loan_id' already exists in expenses";
}

$conn->close();
?>
