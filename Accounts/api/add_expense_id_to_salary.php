<?php
require_once 'config.php';

// Check if column already exists
$checkColumn = "SHOW COLUMNS FROM salary_logs LIKE 'expense_id'";
$result = $conn->query($checkColumn);

if ($result->num_rows == 0) {
    // Add column if not exists
    $sql = "ALTER TABLE salary_logs ADD COLUMN expense_id INT DEFAULT NULL";
    
    if ($conn->query($sql) === TRUE) {
        echo "Column 'expense_id' added successfully to 'salary_logs' table.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'expense_id' already exists.";
}

$conn->close();
?>
