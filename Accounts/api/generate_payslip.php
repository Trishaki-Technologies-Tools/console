<?php
require_once 'config.php';

// Get data from either GET (printing) or DB (viewing)
$refNo = $_GET['refNo'] ?? '';
$p = $_GET; // Fallback to GET params

if ($refNo) {
    $stmt = $conn->prepare("SELECT * FROM payslips WHERE ref_no = ?");
    $stmt->bind_param("s", $refNo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $p = [
            'empName' => $row['employee_name'],
            'empNo' => $row['employee_no'],
            'month' => $row['month_year'],
            'grade' => $row['designation'],
            'bank' => $row['bank_name'],
            'acc' => $row['account_no'],
            'days' => $row['days_paid'],
            'basic' => $row['basic'],
            'hra' => $row['hra'],
            'other' => $row['other'],
            'pf' => $row['pf'],
            'health' => $row['health'],
            'refNo' => $row['ref_no']
        ];
    }
}

// Calculate totals
$totalEarnings = (float)$p['basic'] + (float)$p['hra'] + (float)$p['other'];
$totalDeductions = (float)$p['pf'] + (float)$p['health'];
$netPay = $totalEarnings - $totalDeductions;

// Format month (2026-03 -> MARCH 2026)
$monthLabel = "MONTH YEAR";
if (isset($p['month'])) {
    $monthLabel = strtoupper(date('M Y', strtotime($p['month'] . '-01')));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - <?php echo $p['refNo'] ?? 'New'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; padding: 20px; color: #333; background: #f5f5f5; }
        .payslip-container { background: #fff; width: 800px; margin: 0 auto; padding: 40px; border: 1px solid #ddd; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .header-left h1 { margin: 0; font-size: 28px; font-weight: bold; }
        .header-left p { margin: 5px 0; font-size: 20px; font-weight: 600; color: #444; }
        .header-right { text-align: right; }
        .logo { height: 50px; margin-bottom: 5px; }
        .company-name { font-weight: bold; color: #0077c8; font-size: 16px; margin: 0; }

        .emp-name-bar { background: #f0f4f8; padding: 8px 15px; border: 1px solid #0077c8; margin-bottom: 15px; font-weight: bold; font-size: 16px; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; border: 1px solid #0077c8; margin-bottom: 20px; }
        .details-col { border-right: 1px solid #0077c8; }
        .details-col:last-child { border-right: none; }
        .col-header { 
            background: #0077c8 !important; 
            color: white !important; 
            padding: 5px 10px; 
            font-size: 13px; 
            font-weight: bold; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        td { padding: 4px 10px; border-bottom: 1px solid #eef2f6; }
        .label { font-weight: 600; color: #555; background: #fafbfc; width: 35%; border-right: 1px solid #eef2f6; }

        .earnings-deductions { display: flex; border: 1px solid #0077c8; margin-bottom: 20px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .ed-col { flex: 1; border-right: 1px solid #0077c8; display: flex; flex-direction: column; }
        .ed-col:last-child { border-right: none; }
        .ed-header { 
            background: #0077c8 !important; 
            color: white !important; 
            padding: 8px 10px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            font-weight: bold; 
            font-size: 14px; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            height: 35px;
        }
        .ed-table { background: white; flex-grow: 1; }
        .ed-table td { border-bottom: 1px dotted #ccc; height: 30px; }
        .ed-table td:last-child { text-align: right; width: 35%; font-weight: bold; }
        
        .totals-row { 
            background: #fafbfc !important; 
            font-weight: bold; 
            padding: 10px; 
            display: flex; 
            justify-content: space-between; 
            border-top: 2px solid #0077c8; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .net-pay-section { display: flex; justify-content: flex-end; margin: 20px 0; }
        .net-pay-box { display: flex; border: 2px solid #0077c8; min-width: 320px; }
        .net-pay-label { 
            background: #0077c8 !important; 
            color: white !important; 
            padding: 12px 20px; 
            font-weight: bold; 
            display: flex; 
            align-items: center; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .net-pay-value { padding: 12px 30px; font-size: 22px; font-weight: bold; display: flex; align-items: center; justify-content: center; flex-grow: 1; border-left: 2px solid #0077c8; }

        .tax-info { border: 1px solid #0077c8; margin-top: 40px; }
        .tax-header { 
            background: #0077c8 !important; 
            color: white !important; 
            padding: 5px 10px; 
            font-weight: bold; 
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .tax-footer { font-size: 11px; color: #666; margin-top: 10px; }

        @media print {
            body { 
                background: white; 
                padding: 0; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
            }
            .payslip-container { 
                box-shadow: none; 
                border: 1px solid #0077c8; 
                width: 210mm; 
                padding: 10mm; 
                margin: 0;
            }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0077c8; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Print Payslip</button>
    </div>

    <div class="payslip-container">
        <div class="header">
            <div class="header-left">
                <h1>Payslip</h1>
                <p><?php echo $monthLabel; ?></p>
            </div>
            <div class="header-right">
                <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" class="logo" alt="Company Logo">
                <p class="company-name">TRISHAKI TECHNOLOGIES PRIVATE LIMITED</p>
            </div>
        </div>

        <div class="emp-name-bar">
            MR. <?php echo strtoupper($p['empName'] ?? 'EMPLOYEE NAME'); ?>
        </div>

        <div class="details-grid">
            <div class="details-col">
                <div class="col-header">Employee Details</div>
                <table>
                    <tr><td class="label">Emp No.</td><td><?php echo $p['empNo'] ?? '--'; ?></td></tr>
                    <tr><td class="label">Grade</td><td><?php echo $p['grade'] ?? '--'; ?></td></tr>
                    <tr><td class="label">PAN</td><td><?php echo $p['pan'] ?? 'XXXXXXXXXX'; ?></td></tr>
                </table>
            </div>
            <div class="details-col">
                <div class="col-header">Payment & Leave Details</div>
                <table>
                    <tr><td class="label">Bank Name</td><td><?php echo $p['bank'] ?? '--'; ?></td></tr>
                    <tr><td class="label">Acc No.</td><td><?php echo $p['acc'] ?? '--'; ?></td></tr>
                    <tr><td class="label">Days paid</td><td><?php echo $p['days'] ?? '31'; ?></td></tr>
                </table>
            </div>
            <div class="details-col">
                <div class="col-header">Location Details</div>
                <table>
                    <tr><td class="label">Location</td><td>BELGAUM</td></tr>
                    <tr><td class="label">Ref No</td><td><?php echo $p['refNo'] ?? '--'; ?></td></tr>
                    <tr><td class="label">Tax regime</td><td>NEW</td></tr>
                </table>
            </div>
        </div>

        <div class="earnings-deductions">
            <div class="ed-col">
                <div class="ed-header">
                    <span>Earnings</span>
                    <span style="font-size: 10px;">Amount (INR)</span>
                </div>
                <table class="ed-table">
                    <tr><td>Basic Salary</td><td><?php echo number_format($p['basic'] ?? 0, 2); ?></td></tr>
                    <tr><td>House Rent Allowance</td><td><?php echo number_format($p['hra'] ?? 0, 2); ?></td></tr>
                    <tr><td>Personal Allowance</td><td><?php echo number_format($p['other'] ?? 0, 2); ?></td></tr>
                    <tr><td>Performance Pay</td><td>0.00</td></tr>
                    <tr style="height: 100px;"><td></td><td></td></tr>
                </table>
                <div class="totals-row">
                    <span>Total Earnings</span>
                    <span>₹<?php echo number_format($totalEarnings, 2); ?></span>
                </div>
            </div>
            <div class="ed-col">
                <div class="ed-header">
                    <span>Deductions</span>
                    <span style="font-size: 10px;">Amount (INR)</span>
                </div>
                <table class="ed-table">
                    <tr><td>Provident Fund</td><td><?php echo number_format($p['pf'] ?? 0, 2); ?></td></tr>
                    <tr><td>Health Insurance Scheme</td><td><?php echo number_format($p['health'] ?? 0, 2); ?></td></tr>
                    <tr style="height: 153px;"><td></td><td></td></tr>
                </table>
                <div class="totals-row">
                    <span>Total Deductions</span>
                    <span>₹<?php echo number_format($totalDeductions, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="net-pay-section">
            <div class="net-pay-box">
                <div class="net-pay-label">Net Pay (INR)</div>
                <div class="net-pay-value">₹<?php echo number_format($netPay, 2); ?></div>
            </div>
        </div>

        <div class="tax-info">
            <div class="tax-header">Projected Annual Tax Information</div>
            <table>
                <tr>
                    <td class="label">Annual Income*</td><td><?php echo number_format($totalEarnings * 12, 2); ?></td>
                    <td class="label">Net Tax Income</td><td>0.00</td>
                </tr>
                <tr>
                    <td class="label">Deductions sec 16</td><td>75,000.00</td>
                    <td class="label">Total Tax Payable</td><td>0.00</td>
                </tr>
            </table>
        </div>
        <p class="tax-footer">* Please Note, Annual Income is after considering the exemption - if any.</p>
    </div>
</body>
</html>
