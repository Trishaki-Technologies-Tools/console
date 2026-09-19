<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Ensure clients table and required columns exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS `clients` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50) DEFAULT NULL,
            `email` VARCHAR(255) DEFAULT NULL,
            `gst_number` VARCHAR(100) DEFAULT NULL,
            `address` TEXT DEFAULT NULL,
            `client_type` VARCHAR(50) DEFAULT 'Client',
            `college_name` VARCHAR(255) DEFAULT NULL,
            `department` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $colCheck = $conn->query("SHOW COLUMNS FROM `clients` LIKE 'client_type'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `clients` ADD COLUMN `client_type` VARCHAR(50) DEFAULT 'Client'");
    }
    $colCheck2 = $conn->query("SHOW COLUMNS FROM `clients` LIKE 'college_name'");
    if ($colCheck2 && $colCheck2->num_rows === 0) {
        $conn->query("ALTER TABLE `clients` ADD COLUMN `college_name` VARCHAR(255) DEFAULT NULL");
    }
    $colCheck3 = $conn->query("SHOW COLUMNS FROM `clients` LIKE 'department'");
    if ($colCheck3 && $colCheck3->num_rows === 0) {
        $conn->query("ALTER TABLE `clients` ADD COLUMN `department` VARCHAR(255) DEFAULT NULL");
    }
} catch (Throwable $t) {}

try {
    $fy = getFinancialYearDates();
    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.name,
            c.phone,
            c.email,
            c.gst_number,
            c.address,
            c.client_type,
            c.college_name,
            c.department,
            COUNT(i.id) as invoice_count,
            MAX(i.created_at) as last_invoice_date
        FROM clients c
        LEFT JOIN invoices i ON c.id = i.client_id AND ((i.invoice_date >= ? AND i.invoice_date <= ?) OR (i.invoice_date IS NULL AND DATE(i.created_at) >= ? AND DATE(i.created_at) <= ?))
        WHERE c.created_at >= ? AND c.created_at <= ?
        GROUP BY c.id
        ORDER BY last_invoice_date DESC, c.id DESC
    ");
    $stmt->bind_param("ssssss", $fy['start_date'], $fy['end_date'], $fy['start_date'], $fy['end_date'], $fy['start_datetime'], $fy['end_datetime']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'phone' => $row['phone'],
            'email' => $row['email'] ?: 'N/A',
            'gstNumber' => $row['gst_number'] ?: 'Not Applicable',
            'address' => $row['address'] ?: '',
            'client_type' => $row['client_type'] ?: 'Client',
            'college_name' => $row['college_name'] ?: '',
            'department' => $row['department'] ?: '',
            'invoiceCount' => $row['invoice_count'],
            'lastInvoiceDate' => $row['last_invoice_date']
        ];
    }
    
    echo json_encode($clients);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
