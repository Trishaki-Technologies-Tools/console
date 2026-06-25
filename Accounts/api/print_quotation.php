<?php
require_once 'config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die("Invalid Quotation ID");
}

try {
    // Fetch quotation & client info
    $stmt = $conn->prepare("
        SELECT 
            q.quotation_no,
            q.items,
            q.total_amount,
            q.quotation_date,
            c.name as client_name,
            c.phone,
            c.email,
            c.gst_number as client_gst,
            c.address as client_address
        FROM quotations q
        JOIN clients c ON q.client_id = c.id
        WHERE q.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quote = $result->fetch_assoc();
    $stmt->close();

    if (!$quote) {
        die("Quotation not found");
    }

    // Fetch company settings
    $settingsResult = $conn->query("SELECT `key`, `value` FROM settings");
    $settings = [];
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['key']] = $row['value'];
    }

    // Defaults for company info
    $companyName = $settings['company_name'] ?? 'TRISHAKI TECHNOLOGIES PRIVATE LIMITED';
    $companyAddress = $settings['company_address'] ?? 'F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank, Tilakwadi, Belagavi, Karnataka - 590006';
    $companyPhone = $settings['company_phone'] ?? '+91 9980681304';
    $companyEmail = $settings['company_email'] ?? 'info@trishaki.com';
    $companyGst = $settings['company_gst'] ?? '29ABCDE1234F1Z5';

    $items = json_decode($quote['items'], true) ?: [];

    // Math calculation
    $total = floatval($quote['total_amount']);
    $subtotal = $total / 1.18;
    $gst = $total - $subtotal;

} catch (Exception $e) {
    die("Error generating quotation printable: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation - <?php echo htmlspecialchars($quote['quotation_no']); ?></title>
    <style>
        :root {
            --primary: #4f46e5;
            --text-dark: #0f172a;
            --text-grey: #475569;
            --border: #e2e8f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; padding: 40px; color: var(--text-dark); -webkit-print-color-adjust: exact; }

        .invoice-card {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 25px;
            margin-bottom: 30px;
        }

        .logo-area img { height: 50px; margin-bottom: 8px; }
        .company-details h2 { font-size: 16px; font-weight: 800; color: var(--primary); }
        .company-details p { font-size: 11px; color: var(--text-grey); line-height: 1.5; margin-top: 4px; }

        .quotation-title { text-align: right; }
        .quotation-title h1 { font-size: 26px; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; font-weight: 800; }
        .quote-meta { margin-top: 10px; font-size: 12px; font-weight: 500; color: var(--text-grey); text-align: right; line-height: 1.6; }
        .quote-meta span { color: var(--text-dark); font-weight: 600; }

        .addresses-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 35px;
        }

        .address-box h3 { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-grey); border-bottom: 1px solid var(--border); padding-bottom: 6px; margin-bottom: 10px; }
        .address-box p { font-size: 13px; line-height: 1.5; color: var(--text-dark); }
        .address-box strong { font-size: 14px; display: block; margin-bottom: 4px; color: #000; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8fafc; color: var(--text-grey); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; padding: 12px 16px; text-align: left; border-bottom: 2px solid var(--border); }
        td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid var(--border); color: var(--text-dark); }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-block {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }

        .summary-table { width: 280px; margin-bottom: 0; }
        .summary-table td { padding: 8px 12px; font-size: 13px; border: none; border-bottom: 1px solid #f1f5f9; }
        .summary-table tr.total-row td { font-size: 16px; font-weight: 800; color: var(--primary); border-top: 2px solid var(--border); border-bottom: none; }

        .terms-conditions {
            margin-top: 50px;
            font-size: 11px;
            color: var(--text-grey);
            line-height: 1.6;
        }
        .terms-conditions h4 { font-size: 12px; font-weight: 700; color: var(--text-dark); margin-bottom: 6px; }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .sig-col { text-align: center; width: 180px; }
        .sig-line { border-top: 1px solid var(--text-dark); margin-bottom: 8px; }
        .sig-label { font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-grey); }

        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        @media print {
            body { background: white; padding: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 0; width: 100%; }
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Quotation</button>

    <div class="invoice-card">
        <div class="header-row">
            <div class="company-details">
                <div class="logo-area">
                    <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="Company Logo" onerror="this.src='https://via.placeholder.com/150x50/f8fafc/4f46e5?text=TriShaKi'">
                </div>
                <h2><?php echo htmlspecialchars($companyName); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($companyAddress)); ?><br>
                <strong>Phone:</strong> <?php echo htmlspecialchars($companyPhone); ?> | <strong>Email:</strong> <?php echo htmlspecialchars($companyEmail); ?><br>
                <strong>GSTIN:</strong> <?php echo htmlspecialchars($companyGst); ?></p>
            </div>
            <div class="quotation-title">
                <h1>Quotation</h1>
                <div class="quote-meta">
                    QUOTATION NO: <span><?php echo htmlspecialchars($quote['quotation_no']); ?></span><br>
                    DATE: <span><?php echo date('d-M-Y', strtotime($quote['quotation_date'])); ?></span><br>
                    VALID UNTIL: <span><?php echo date('d-M-Y', strtotime($quote['quotation_date'] . ' + 30 days')); ?></span>
                </div>
            </div>
        </div>

        <div class="addresses-row">
            <div class="address-box">
                <h3>Quotation Prepared For</h3>
                <strong><?php echo htmlspecialchars($quote['client_name']); ?></strong>
                <p>
                    <?php if (!empty($quote['client_address'])): ?>
                        <?php echo nl2br(htmlspecialchars($quote['client_address'])); ?><br>
                    <?php endif; ?>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($quote['phone']); ?><br>
                    <?php if (!empty($quote['email']) && $quote['email'] !== 'N/A'): ?>
                        <strong>Email:</strong> <?php echo htmlspecialchars($quote['email']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($quote['client_gst']) && $quote['client_gst'] !== 'Not Applicable'): ?>
                        <strong>GSTIN:</strong> <?php echo htmlspecialchars($quote['client_gst']); ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="address-box">
                <h3>Terms & Project Overview</h3>
                <p>
                    <strong>Subject:</strong> Service/Product Delivery Agreement<br>
                    <strong>Payment Terms:</strong> 50% Advance, 50% Upon completion.<br>
                    <strong>Estimate Duration:</strong> As agreed per project scope.<br>
                    All values listed below are in INR.
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 55px;" class="text-center">#</th>
                    <th>Description</th>
                    <th style="width: 80px;" class="text-center">Qty</th>
                    <th style="width: 120px;" class="text-right">Unit Rate (₹)</th>
                    <th style="width: 130px;" class="text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                    <td class="text-center"><?php echo intval($item['qty'] ?? 1); ?></td>
                    <td class="text-right"><?php echo number_format(floatval($item['rate'] ?? 0), 2); ?></td>
                    <td class="text-right"><?php echo number_format(floatval($item['amount'] ?? 0), 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="summary-block">
            <table class="summary-table">
                <tr>
                    <td>Subtotal (Tax Excl.):</td>
                    <td class="text-right">₹<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <tr>
                    <td>Estimated GST (18%):</td>
                    <td class="text-right">₹<?php echo number_format($gst, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total Quote:</td>
                    <td class="text-right">₹<?php echo number_format($total, 2); ?></td>
                </tr>
            </table>
        </div>

        <div class="terms-conditions">
            <h4>Terms & Conditions</h4>
            <p>1. Prices quoted are inclusive of 18% GST unless specified otherwise.<br>
            2. Payment schedule: 50% advance on sign-off, remaining 50% within 7 days of milestone completion.<br>
            3. This proposal is valid for 30 days from the date of issue.<br>
            4. Service warranties and support details are bound by individual project master agreements.</p>
        </div>

        <div class="signatures">
            <div class="sig-col">
                <div class="sig-line"></div>
                <span class="sig-label">Accepted By (Client)</span>
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <span class="sig-label">Prepared By (Authorized Signatory)</span>
            </div>
        </div>
    </div>
</body>
</html>
