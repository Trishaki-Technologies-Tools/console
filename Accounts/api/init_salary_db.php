<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS salary_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_name VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    month VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'Paid',
    payment_date DATE NOT NULL,
    payment_mode VARCHAR(50) DEFAULT 'Bank Transfer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table salary_logs created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
