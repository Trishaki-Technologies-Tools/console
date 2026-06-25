<?php
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    // Ensure payslips table exists
    $conn->query("CREATE TABLE IF NOT EXISTS payslips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_no VARCHAR(50) NOT NULL UNIQUE,
        employee_name VARCHAR(255) NOT NULL,
        employee_no VARCHAR(100) NOT NULL,
        month_year VARCHAR(50) NOT NULL,
        designation VARCHAR(255),
        bank_name VARCHAR(255),
        account_no VARCHAR(100),
        days_paid INT,
        basic DECIMAL(10, 2),
        hra DECIMAL(10, 2),
        other DECIMAL(10, 2),
        pf DECIMAL(10, 2),
        health DECIMAL(10, 2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Generate reference number: PS-YEAR-MONTH-XXX
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payslips WHERE ref_no LIKE ?");
    $pattern = "PS-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nextNumber = $row['count'] + 1;
    $refNo = 'PS-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    // Save
    $stmt = $conn->prepare("INSERT INTO payslips (ref_no, employee_name, employee_no, month_year, designation, bank_name, account_no, days_paid, basic, hra, other, pf, health) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssssiddddd", 
        $refNo, 
        $data['empName'], 
        $data['empNo'], 
        $data['month'], 
        $data['grade'], 
        $data['bank'], 
        $data['acc'], 
        $data['days'], 
        $data['basic'], 
        $data['hra'], 
        $data['other'], 
        $data['pf'], 
        $data['health']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'refNo' => $refNo]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
