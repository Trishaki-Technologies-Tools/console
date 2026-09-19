<?php
require_once __DIR__ . '/../api/config.php';

$sql = "CREATE TABLE IF NOT EXISTS receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(50) NOT NULL UNIQUE,
    client_id INT(11) NOT NULL,
    type ENUM('gst','non-gst') NOT NULL,
    items LONGTEXT NOT NULL,
    original_total_payable DECIMAL(10,2) NOT NULL,
    cumulative_total_paid DECIMAL(10,2) DEFAULT '0.00',
    receipt_date DATE NOT NULL,
    status ENUM('unpaid','partially_paid','paid','cancelled') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_receipt_no (receipt_no),
    INDEX idx_type (type),
    INDEX idx_date (receipt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql)) {
    echo "Receipts table created successfully!\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}
?>
