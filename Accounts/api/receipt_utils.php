<?php
// Shared Utilities for Receipt Generation

function generateReceiptNumber($conn, $clientId, $type, $continueFrom, $items) {
    $year = date('Y');
    
    // Find the maximum receipt number suffix for the current year to prevent duplicate entry errors
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
        if (preg_match('/^RECP-\d{4}-(\d+)/', $recNo, $matches)) {
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
