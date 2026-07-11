<?php
require_once 'config.php';
require_once 'receipt_utils.php';

// Fetch all receipts
$query = "SELECT r.*, c.name as billToName, c.phone, c.email, c.gst_number as gstNumber 
          FROM receipts r 
          JOIN clients c ON r.client_id = c.id 
          ORDER BY r.id ASC";
$result = $conn->query($query);

if ($result->num_rows === 0) {
    die("No receipts found to print.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Receipts Print - TriShaKi Technologies</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: 'Inter', sans-serif; }
        .bulk-container { display: flex; flex-direction: column; gap: 40px; align-items: center; padding: 40px 0; }
        .receipt-wrapper { background: white; box-shadow: 0 0 20px rgba(0,0,0,0.1); width: 210mm; min-height: 297mm; }
        
        @media print {
            body { background: white; }
            .bulk-container { padding: 0; gap: 0; }
            .receipt-wrapper { box-shadow: none; page-break-after: always; width: 100%; border: none !important; }
            .no-print { display: none; }
        }

        .print-fab {
            position: fixed; bottom: 30px; right: 30px; 
            background: #0077c8; color: white; border: none; 
            padding: 15px 25px; border-radius: 50px; font-size: 16px; 
            font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000; display: flex; align-items: center; gap: 10px;
        }
    </style>
</head>
<body>
    <button class="print-fab no-print" onclick="window.print()">
        <span>🖨️</span> Print / Save All as PDF
    </button>

    <div class="bulk-container">
        <?php 
        while($rec = $result->fetch_assoc()): 
            $bulkPrintMode = true;
            
            // Set variables expected by generate_receipt.php
            $type = $rec['type'];
            $billToName = $rec['billToName'];
            $phone = $rec['phone'];
            $email = $rec['email'];
            $gstNumber = $rec['gstNumber'];
            $address = '';
            $date = $rec['receipt_date'];
            $receiptNo = $rec['receipt_no'];
            $items = json_decode($rec['items'], true);
            $originalTotalPayable = $rec['original_total_payable'];
            $cumulativeTotalPaid = $rec['cumulative_total_paid']; 

            echo '<div class="receipt-wrapper">';
            include 'generate_receipt.php';
            echo '</div>';
            
        endwhile; 
        ?>
    </div>
</body>
</html>
