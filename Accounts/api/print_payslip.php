<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    die("Invalid request");
}

// Fetch Salary Log and Employee Details
$stmt = $conn->prepare("
    SELECT 
        sl.id, sl.month, sl.amount, sl.status, sl.payment_date,
        pm.mode_name AS payment_mode,
        e.name AS employee_name, e.employee_no, e.designation, e.bank_name, e.account_no
    FROM salary_logs sl
    JOIN employees e ON sl.employee_id = e.id
    LEFT JOIN payment_modes pm ON sl.payment_mode_id = pm.id
    WHERE sl.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$salary = $result->fetch_assoc();
$stmt->close();

if (!$salary) {
    die("Salary record not found");
}

// Fetch company settings
$settingsResult = $conn->query("SELECT setting_key AS `key`, setting_value AS `value` FROM settings");
$settings = [];
while ($row = $settingsResult->fetch_assoc()) {
    $settings[$row['key']] = $row['value'];
}

$companyName = $settings['company_name'] ?? 'TRISHAKI TECHNOLOGIES PRIVATE LIMITED';
$companyAddress = $settings['company_address'] ?? 'F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank, Tilakwadi, Belagavi, Karnataka - 590006';
$companyPhone = $settings['company_phone'] ?? '99806 81304';
$companyEmail = $settings['company_email'] ?? 'info@trishaki.com';

// Format Data
$monthLabel = date('F Y', strtotime($salary['month'] . '-01'));
$paymentDateLabel = date('F d, Y', strtotime($salary['payment_date']));
$receiptNo = 'SAL-' . str_pad($salary['id'], 5, '0', STR_PAD_LEFT);
$amount = floatval($salary['amount']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - <?php echo htmlspecialchars($salary['employee_name'] . ' - ' . $monthLabel); ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        :root {
            --theme-color: #1e3a8a; /* Deep corporate blue */
            --theme-light: #e0f2fe; /* Light blue for subtle accents */
            --border-color: #cbd5e1; 
            --text-color: #000000;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f1f5f9;
            padding: 40px;
            color: var(--text-color);
            -webkit-print-color-adjust: exact;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body {
                background: white;
                padding: 0 !important;
                margin: 0 !important;
            }
            .document-container {
                width: 100% !important;
                min-height: 100% !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .print-button { display: none; }
        }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1e293b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            z-index: 1000;
        }

        .document-container {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #cbd5e1;
            display: flex;
            flex-direction: column;
        }

        /* Top Header Area (Pale Green) */
        .doc-header {
            background-color: var(--theme-color);
            color: #ffffff;
            padding: 25px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            max-width: 70%;
        }

        .header-left h1 {
            font-size: 18pt;
            font-weight: 700;
            margin-bottom: 5px;
            color: #ffffff;
            text-transform: capitalize;
        }

        .header-left p {
            font-size: 9.5pt;
            line-height: 1.4;
            color: #e2e8f0;
        }

        .header-right {
            text-align: right;
        }

        .company-logo {
            max-height: 70px;
            object-fit: contain;
        }

        /* Content Area */
        .doc-content {
            padding: 30px 40px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        
        .info-table td {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            font-size: 10pt;
            width: 50%;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border-color);
            margin-bottom: 25px;
        }

        .data-table th {
            background-color: var(--theme-color);
            color: #ffffff;
            padding: 8px 12px;
            font-size: 9pt;
            text-align: left;
            text-transform: uppercase;
            font-weight: 700;
            border: 1px solid var(--border-color);
        }

        .data-table td {
            padding: 8px 12px;
            font-size: 10pt;
            border: 1px solid var(--border-color);
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        
        .font-bold { font-weight: 700; }

        .total-row {
            font-weight: 700;
        }
        .total-row td {
            border-top: 1px solid var(--border-color);
        }

        /* Summary Blocks */
        .summary-block {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }

        .summary-block td {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            font-size: 10pt;
        }

        .summary-label {
            background-color: var(--theme-color);
            color: #ffffff;
            font-weight: 700;
            text-transform: uppercase;
            width: 65%;
        }
        
        .summary-val {
            text-align: right;
            font-weight: 700;
        }

        /* Signatures (Hidden to match design exactly, but kept in case) */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 40px;
        }
        .sig-col {
            width: 250px;
            text-align: center;
        }
        .sig-line {
            border-top: 1px solid var(--text-color);
            margin-bottom: 5px;
        }
        .sig-label {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Payslip</button>

    <div class="document-container">
        
        <!-- Header area with Pale Green background -->
        <div class="doc-header">
            <div class="header-left">
                <h1><?php echo htmlspecialchars($companyName); ?></h1>
                <p>
                    <?php 
                    // Make address single line or simple breaks
                    echo str_replace("\n", " ", htmlspecialchars($companyAddress)) . "<br>";
                    echo htmlspecialchars($companyEmail) . " / " . htmlspecialchars($companyPhone);
                    ?>
                </p>
            </div>
            <div class="header-right">
                <img class="company-logo" src="../assets/trishaki_round_logo_no_border.png" alt="Company Logo" onerror="this.src='https://via.placeholder.com/150x70/f1f5f9/a3b18a?text=Logo'">
            </div>
        </div>

        <div class="doc-content">
            <div class="doc-title">Payslip</div>

            <!-- Two-column info table -->
            <table class="info-table">
                <tr>
                    <td><span class="font-bold">Employee Name:</span> <?php echo htmlspecialchars($salary['employee_name']); ?></td>
                    <td><span class="font-bold">Pay Stub No:</span> <?php echo $receiptNo; ?></td>
                </tr>
                <tr>
                    <td><span class="font-bold">Employee ID:</span> <?php echo htmlspecialchars($salary['employee_no'] ?: 'N/A'); ?></td>
                    <td><span class="font-bold">Pay Period:</span> <?php echo $monthLabel; ?></td>
                </tr>
                <tr>
                    <td><span class="font-bold">Designation:</span> <?php echo htmlspecialchars($salary['designation'] ?: 'N/A'); ?></td>
                    <td><span class="font-bold">Pay Date:</span> <?php echo $paymentDateLabel; ?></td>
                </tr>
            </table>

            <!-- Earnings Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th colspan="2">EARNINGS</th>
                    </tr>
                    <tr>
                        <th style="width: 70%;">DESCRIPTION</th>
                        <th class="text-right" style="width: 30%;">CURRENT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Basic Pay / Net Salary</td>
                        <td class="text-right">₹<?php echo number_format($amount, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Allowances / Bonus</td>
                        <td class="text-right">₹0.00</td>
                    </tr>
                    <!-- Empty rows for padding -->
                    <tr><td style="color:transparent;user-select:none;">-</td><td></td></tr>
                    <tr><td style="color:transparent;user-select:none;">-</td><td></td></tr>
                    <tr class="total-row">
                        <td>GROSS EARNINGS</td>
                        <td class="text-right">₹<?php echo number_format($amount, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Deductions Table -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th colspan="2">DEDUCTIONS</th>
                    </tr>
                    <tr>
                        <th style="width: 70%;">DESCRIPTION</th>
                        <th class="text-right" style="width: 30%;">CURRENT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Taxes / Professional Tax</td>
                        <td class="text-right">₹0.00</td>
                    </tr>
                    <tr>
                        <td>Absences / Undertime</td>
                        <td class="text-right">₹0.00</td>
                    </tr>
                    <!-- Empty rows for padding -->
                    <tr><td style="color:transparent;user-select:none;">-</td><td></td></tr>
                    <tr><td style="color:transparent;user-select:none;">-</td><td></td></tr>
                    <tr class="total-row">
                        <td>GROSS DEDUCTIONS</td>
                        <td class="text-right">₹0.00</td>
                    </tr>
                </tbody>
            </table>

            <!-- Final Summary Block -->
            <table class="summary-block">
                <tr>
                    <td class="summary-label">CURRENT NET PAY</td>
                    <td class="summary-val">₹<?php echo number_format($amount, 2); ?></td>
                </tr>
            </table>

            <?php if ($salary['status'] === 'Paid'): ?>
                <div style="text-align: right; font-weight: 700; color: #166534; font-size: 11pt; margin-top: -15px;">
                    STATUS: PAID VIA <?php echo strtoupper(htmlspecialchars($salary['payment_mode'] ?: 'Bank Transfer')); ?>
                </div>
            <?php endif; ?>

            <!-- Signatures (Optional) -->
            <div class="signatures">
                <div class="sig-col">
                    <div style="height: 60px;"></div>
                    <div class="sig-line"></div>
                    <span class="sig-label">Employee Signature</span>
                </div>
                <div class="sig-col" style="position: relative;">
                    <img src="../assets/ningaraj_sign_blue.png" alt="Signature" style="position: absolute; bottom: -40px; left: 50%; transform: translateX(-50%) rotate(5deg); height: 240px; max-width: 400px; object-fit: contain; pointer-events: none; z-index: 1;" onerror="this.style.display='none'">
                    <div style="height: 60px;"></div>
                    <div class="sig-line" style="position: relative; z-index: 2;"></div>
                    <span class="sig-label" style="position: relative; z-index: 2;">Authorized Signatory</span>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
