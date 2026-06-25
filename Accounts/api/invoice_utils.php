<?php
// Shared Utilities for Invoice Generation

function generateInvoiceNumber($conn, $customerId, $type, $continueFrom, $items) {
    $year = date('Y');
    
    // Check if current invoice is a partial payment
    $itemsArray = json_decode($items, true);
    $currentTotalPayable = 0;
    $currentTotalPaid = 0;
    
    foreach ($itemsArray as $item) {
        $currentTotalPayable += floatval($item['totalInclTax'] ?? $item['amount'] ?? 0);
        $currentTotalPaid += floatval($item['paidAmt'] ?? $item['amount'] ?? 0);
    }
    
    $isPartialPayment = $currentTotalPayable > $currentTotalPaid;
    
    // If continuing from existing invoice (user clicked "Continue Part Payment" OR Bulk row found)
    if ($continueFrom) {
        // Extract base invoice number and increment payment number
        if (preg_match('/^(TSK-\d{4}-\d{3})(?:\/P(\d+))?$/', $continueFrom, $matches)) {
            $baseInvoice = $matches[1];
            $paymentNum = isset($matches[2]) ? intval($matches[2]) + 1 : 2;
            return $baseInvoice . '/P' . $paymentNum;
        }
    }
    
    // Generate new invoice number - count distinct base invoice numbers
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT 
            CASE 
                WHEN invoice_no LIKE '%/P%' THEN SUBSTRING_INDEX(invoice_no, '/P', 1)
                ELSE invoice_no
            END
        ) as count 
        FROM invoices 
        WHERE invoice_no LIKE ?
    ");
    $pattern = "TSK-$year-%";
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $nextNumber = $row['count'] + 1;
    
    $baseInvoiceNo = 'TSK-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    
    // If this is a partial payment, add /P1
    if ($isPartialPayment) {
        return $baseInvoiceNo . '/P1';
    }
    
    return $baseInvoiceNo;
}
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
?>
