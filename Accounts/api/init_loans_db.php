<?php
require_once 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creditor_name VARCHAR(255) NOT NULL,
    principal_amount DECIMAL(10, 2) NOT NULL,
    interest_rate DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    start_date DATE NOT NULL,
    status ENUM('Active', 'Closed') DEFAULT 'Active',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'loans' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
