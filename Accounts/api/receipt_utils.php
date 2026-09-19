<?php
// Shared Utilities for Receipt Generation

function ensureReceiptsTableExists($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS receipts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        receipt_no VARCHAR(50) NOT NULL UNIQUE,
        client_id INT(11) NOT NULL,
        invoice_no VARCHAR(50) DEFAULT NULL,
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
        INDEX idx_invoice_no (invoice_no),
        INDEX idx_type (type),
        INDEX idx_date (receipt_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    try {
        $conn->query($sql);
    } catch (Throwable $e) {}

    // Check if invoice_no column already exists before attempting ALTER TABLE
    try {
        $colCheck = $conn->query("SHOW COLUMNS FROM receipts LIKE 'invoice_no'");
        if ($colCheck && $colCheck->num_rows === 0) {
            $conn->query("ALTER TABLE receipts ADD COLUMN invoice_no VARCHAR(50) DEFAULT NULL AFTER client_id");
        }
    } catch (Throwable $e) {}
}

function generateReceiptNumber($conn, $clientId, $type, $continueFrom, $items, $year = null) {
    ensureReceiptsTableExists($conn);
    $year = $year ? strval($year) : (function_exists('getFinancialYearYearFromDate') ? getFinancialYearYearFromDate(date('Y-m-d')) : date('Y'));
    
    // Find the maximum receipt number suffix for that year to prevent duplicate entry errors
    $stmt = $conn->prepare("
        SELECT receipt_no 
        FROM receipts 
        WHERE receipt_no LIKE ?
    ");
    $pattern = "RECP-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $maxNum = 0;
    while ($row = $result->fetch_assoc()) {
        $recNo = $row['receipt_no'];
        if (preg_match('/^RECP-' . $year . '-(\d+)/', $recNo, $matches)) {
            $num = intval($matches[1]);
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    
    $nextNumber = $maxNum + 1;
    return 'RECP-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

if (!function_exists('numberToWords')) {
    function numberToWords($number) {
        if ($number === null || $number === '') return '';
        $number = (int)$number;
        $ones = array(0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen');
        $tens = array(2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety');
        
        if ($number == 0) return 'Zero';
        $words = '';
        if ($number >= 10000000) { $words .= numberToWords((int)($number / 10000000)) . ' Crore '; $number %= 10000000; }
        if ($number >= 100000) { $words .= numberToWords((int)($number / 100000)) . ' Lakh '; $number %= 100000; }
        if ($number >= 1000) { $words .= numberToWords((int)($number / 1000)) . ' Thousand '; $number %= 1000; }
        if ($number >= 100) { $words .= $ones[(int)($number / 100)] . ' Hundred '; $number %= 100; }
        if ($number >= 20) { $words .= $tens[(int)($number / 10)] . ' '; $number %= 10; }
        if ($number > 0) { $words .= $ones[$number] . ' '; }
        return trim($words);
    }
}
?>
