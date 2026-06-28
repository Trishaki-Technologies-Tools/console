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
    $settingsResult = $conn->query("SELECT setting_key AS `key`, setting_value AS `value` FROM settings");
    $settings = [];
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['key']] = $row['value'];
    }

    // Defaults for company info
    $companyName = $settings['company_name'] ?? 'TRISHAKI TECHNOLOGIES PRIVATE LIMITED';
    $companyAddress = $settings['company_address'] ?? 'F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank, Tilakwadi, Belagavi, Karnataka - 590006';
    $companyPhone = $settings['company_phone'] ?? '+91 9980681304';
    $companyEmail = $settings['company_email'] ?? 'info@trishaki.com';
    $companyGst = $settings['company_gst'] ?? '29AAGCT8028D1ZS';

    $itemsDecoded = json_decode($quote['items'], true) ?: [];

    // Normalize format
    if (isset($itemsDecoded['commercial_items'])) {
        $projectName = $itemsDecoded['project_name'] ?? 'Project Proposal';
        $projectDesc = $itemsDecoded['project_description'] ?? '';
        $commercialItems = $itemsDecoded['commercial_items'] ?? [];
        $discount = floatval($itemsDecoded['discount'] ?? 0);
        $gstPercent = isset($itemsDecoded['gst_percent']) ? floatval($itemsDecoded['gst_percent']) : 18;
        $scopeOfWork = $itemsDecoded['scope_of_work'] ?? [];
        $terms = $itemsDecoded['terms'] ?? [];
        $clientSignatureName = $itemsDecoded['client_signature_name'] ?? '';
        $clientSignatureDate = $itemsDecoded['client_signature_date'] ?? '';
        $includeScope = isset($itemsDecoded['include_scope']) ? (bool)$itemsDecoded['include_scope'] : true;
    } else {
        // Legacy flat format
        $projectName = 'Project Proposal';
        $projectDesc = '';
        $commercialItems = $itemsDecoded;
        $discount = 0;
        $gstPercent = 18;
        $scopeOfWork = [];
        $includeScope = false;
        $terms = [];
        $clientSignatureName = '';
        $clientSignatureDate = '';
    }

    // Check for legacy terms or empty terms
    $isLegacyTerms = false;
    foreach ($terms as $t) {
        $titleLower = strtolower($t['title'] ?? '');
        if (strpos($titleLower, 'payment') !== false || strpos($titleLower, 'timeline') !== false || strpos($titleLower, 'validity') !== false) {
            $isLegacyTerms = true;
            break;
        }
    }
    if (empty($terms) || count($terms) < 6 || $isLegacyTerms) {
        $terms = [
            ['title' => '1', 'content' => 'This quotation is valid for 15 days from the date of issue.'],
            ['title' => '2', 'content' => 'Project will commence upon receipt of the agreed advance payment.'],
            ['title' => '3', 'content' => 'This quotation covers only the scope of work mentioned herein.'],
            ['title' => '4', 'content' => 'Additional features or changes requested after approval will be quoted separately.'],
            ['title' => '5', 'content' => 'The client shall provide all required content and approvals on time.'],
            ['title' => '6', 'content' => 'Delays in client approvals may extend the project timeline.'],
            ['title' => '7', 'content' => 'Third-party charges (if applicable) shall be borne by the client unless otherwise specified.'],
            ['title' => '8', 'content' => 'Project ownership will be transferred upon receipt of full payment.'],
            ['title' => '9', 'content' => 'Advance payment is non-refundable once the project has commenced.'],
            ['title' => '10', 'content' => 'By signing this quotation, the client agrees to the above Terms & Conditions']
        ];
    }

    // Mathematical calculations (Inclusive GST)
    $totalAmt = 0;
    foreach ($commercialItems as $item) {
        $totalAmt += floatval($item['rate'] ?? 0);
    }
    $grandTotal = max(0, $totalAmt - $discount);
    $subtotal = $grandTotal / (1 + ($gstPercent / 100));
    $gstAmount = $grandTotal - $subtotal;
    $halfGstPercent = $gstPercent / 2;
    $halfGstAmount = $gstAmount / 2;

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
            --border: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; padding: 40px; color: var(--text-dark); -webkit-print-color-adjust: exact; }

        .invoice-card {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 30px auto;
            padding: 15mm 20mm;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .company-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .company-logo {
            height: 55px;
            width: auto;
            object-fit: contain;
        }
        .company-info h2 {
            font-size: 13px;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        .company-info p {
            font-size: 9px;
            color: var(--text-grey);
            line-height: 1.35;
        }
        .company-info .company-contact {
            font-size: 9px;
            margin-top: 2px;
            color: var(--text-dark);
        }

        .quotation-title { text-align: right; }
        .quotation-title h1 { font-size: 20px; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 800; line-height: 1.1; }
        .quote-meta { margin-top: 4px; font-size: 9px; color: var(--text-grey); text-align: right; line-height: 1.35; }
        .quote-meta span { color: var(--text-dark); font-weight: 600; }

        .addresses-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }

        .address-box h3 { font-size: 9px; font-weight: 700; text-transform: uppercase; color: var(--text-grey); border-bottom: 1px solid var(--border); padding-bottom: 3px; margin-bottom: 6px; }
        .address-box p { font-size: 10px; line-height: 1.35; color: #1e293b; }
        .address-box strong { font-size: 11px; display: block; margin-bottom: 2px; color: #0f172a; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; border: 1px solid var(--border); }
        th { background: #f8fafc; color: var(--text-grey); font-weight: 700; text-transform: uppercase; font-size: 8.5px; padding: 6px 10px; text-align: left; border: 1px solid var(--border); border-bottom: 2px solid var(--border); }
        td { padding: 6px 10px; font-size: 10px; border: 1px solid var(--border); color: #1e293b; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .summary-block {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 12px;
        }

        .summary-table { width: 250px; margin-bottom: 0; border: 1px solid var(--border); border-collapse: collapse; }
        .summary-table td { padding: 5px 8px; font-size: 10px; border: 1px solid var(--border); }
        .summary-table tr.total-row td { font-size: 11px; font-weight: 800; color: var(--primary); background: #f8fafc; }

        .terms-conditions {
            margin-top: 10px;
            font-size: 9px;
            color: var(--text-grey);
            line-height: 1.3;
            flex-grow: 1;
        }
        .terms-conditions h4 { font-size: 10px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; border-bottom: 1px solid var(--border); padding-bottom: 2px; }
        .term-section { display: flex; align-items: flex-start; margin-bottom: 4px; font-size: 9px; }
        .term-section strong { min-width: 22px; flex-shrink: 0; color: var(--text-dark); font-weight: 700; }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #f1f5f9;
        }
        .sig-col { text-align: center; width: 170px; }
        .sig-line { border-top: 1px solid var(--text-dark); margin-top: 4px; margin-bottom: 3px; }
        .sig-label { font-size: 8.5px; font-weight: 700; text-transform: uppercase; color: var(--text-grey); }

        .scope-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .module-item {
            margin-bottom: 15px;
        }

        .module-name {
            font-size: 12px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
        }

        .module-desc {
            font-size: 11px;
            color: var(--text-grey);
            margin-bottom: 6px;
            font-style: italic;
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 15px;
            padding-left: 10px;
        }

        .feature-bullet {
            font-size: 11px;
            color: #334155;
            position: relative;
            padding-left: 12px;
        }

        .feature-bullet::before {
            content: "•";
            color: var(--primary);
            font-weight: 800;
            position: absolute;
            left: 0;
        }

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
            z-index: 1000;
        }

        @media print {
            @page {
                size: A4;
                margin: 10mm;
            }
            body { background: white; padding: 0; margin: 0; }
            .invoice-card { box-shadow: none; border: none; padding: 4mm 0; width: 100%; margin: 0; min-height: auto; }
            .print-button { display: none; }
            .page-break { page-break-before: always; break-before: page; margin-top: 0; }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">🖨️ Print Quotation</button>

    <!-- Page 1: Commercial Details & Terms -->
    <div class="invoice-card">
        <div class="header-row">
            <div class="company-brand">
                <img class="company-logo" src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="Company Logo" onerror="this.src='https://via.placeholder.com/150x50/f8fafc/4f46e5?text=TriShaKi'">
                <div class="company-info">
                    <h2><?php echo htmlspecialchars($companyName); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($companyAddress)); ?></p>
                    <p class="company-contact">
                        <strong>Phone:</strong> <?php echo htmlspecialchars($companyPhone); ?> | <strong>Email:</strong> <?php echo htmlspecialchars($companyEmail); ?> | <strong>GSTIN:</strong> <?php echo htmlspecialchars($companyGst); ?>
                    </p>
                </div>
            </div>
            <div class="quotation-title">
                <h1>Quotation</h1>
                <div class="quote-meta">
                    QUOTATION NO: <span><?php echo htmlspecialchars($quote['quotation_no']); ?></span><br>
                    DATE: <span><?php echo date('d-M-Y', strtotime($quote['quotation_date'])); ?></span><br>
                    VALID UNTIL: <span><?php echo date('d-M-Y', strtotime($quote['quotation_date'] . ' + 15 days')); ?></span>
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
                <h3>Project Overview</h3>
                <p>
                    <strong>Project:</strong> <?php echo htmlspecialchars($projectName); ?><br>
                    <?php if (!empty($projectDesc)): ?>
                        <strong>Description:</strong> <?php echo htmlspecialchars($projectDesc); ?><br>
                    <?php endif; ?>
                    All values listed below are in INR.
                </p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 45px;" class="text-center">#</th>
                    <th>Description</th>
                    <th style="width: 150px;" class="text-right">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($commercialItems as $item): ?>
                <tr>
                    <td class="text-center"><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
                    <td class="text-right">₹<?php echo number_format(floatval($item['rate'] ?? 0), 2); ?></td>
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
                <?php if ($discount > 0): ?>
                <tr>
                    <td>Discount:</td>
                    <td class="text-right" style="color: #ef4444;">-₹<?php echo number_format($discount, 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>CGST (<?php echo $halfGstPercent; ?>%):</td>
                    <td class="text-right">₹<?php echo number_format($halfGstAmount, 2); ?></td>
                </tr>
                <tr>
                    <td>SGST (<?php echo $halfGstPercent; ?>%):</td>
                    <td class="text-right">₹<?php echo number_format($halfGstAmount, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Grand Total:</td>
                    <td class="text-right">₹<?php echo number_format($grandTotal, 2); ?></td>
                </tr>
            </table>
        </div>

        <div class="terms-conditions">
            <h4>Terms & Conditions</h4>
            <?php foreach ($terms as $t): ?>
                <div class="term-section">
                    <?php if (preg_match('/^\d+$/', $t['title'])): ?>
                        <strong><?php echo htmlspecialchars($t['title']); ?>.</strong> <?php echo htmlspecialchars($t['content']); ?>
                    <?php else: ?>
                        <strong><?php echo htmlspecialchars($t['title']); ?>:</strong> <?php echo htmlspecialchars($t['content']); ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

         <div class="signatures">
             <div class="sig-col">
                 <div style="border: 1px dashed #cbd5e1; height: 35px; width: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 8px; border-radius: 4px; background: #fafafa; margin-bottom: 4px;">
                     Client Seal / Signature Box
                 </div>
                 <div class="sig-line"></div>
                 <span class="sig-label">Accepted By (Client)</span>
                 <?php if (!empty($clientSignatureName)): ?>
                     <div style="font-size: 9px; color: #334155; font-weight: bold; margin-top: 2px;"><?php echo htmlspecialchars($clientSignatureName); ?></div>
                 <?php endif; ?>
                 <?php if (!empty($clientSignatureDate)): ?>
                     <div style="font-size: 8px; color: #64748b;">Date: <?php echo date('d-M-Y', strtotime($clientSignatureDate)); ?></div>
                 <?php endif; ?>
             </div>
             <div class="sig-col" style="position: relative;">
                 <div style="height: 35px; display: flex; align-items: flex-end; justify-content: center;">
                     <img src="../assets/ningaraj_sign_blue.png" alt="Company Sign" style="height: 42px; max-width: 130px; object-fit: contain; margin-bottom: -5px; transform: rotate(-2deg);" onerror="this.style.display='none'">
                 </div>
                 <div class="sig-line"></div>
                 <span class="sig-label">Prepared By (Authorized Signatory)</span>
                 <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">TriShaKi Technologies</div>
             </div>
         </div>
     </div>

    <!-- Page 2: Scope of Work (Printable page break) -->
    <?php if ($includeScope && !empty($scopeOfWork)): ?>
    <div class="invoice-card page-break">
        <div class="header-row">
            <div class="company-brand">
                <img class="company-logo" src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="Company Logo" onerror="this.src='https://via.placeholder.com/150x50/f8fafc/4f46e5?text=TriShaKi'">
                <div class="company-info">
                    <h2><?php echo htmlspecialchars($companyName); ?></h2>
                </div>
            </div>
            <div class="quotation-title">
                <h1>Project Scope</h1>
                <div class="quote-meta">
                    PROJECT: <span><?php echo htmlspecialchars($projectName); ?></span>
                </div>
            </div>
        </div>

        <div class="scope-title">Scope of Work & Features List</div>

        <?php foreach ($scopeOfWork as $mod): ?>
            <div class="module-item">
                <div class="module-name">
                    <span><?php echo htmlspecialchars($mod['module_name']); ?></span>
                    <span style="font-size: 8px; color: #4f46e5; background: #e0e7ff; padding: 2px 6px; border-radius: 10px; font-weight: 700;"><?php echo count($mod['features'] ?? []); ?> Features</span>
                </div>
                <?php if (!empty($mod['description'])): ?>
                    <div class="module-desc"><?php echo htmlspecialchars($mod['description']); ?></div>
                <?php endif; ?>
                <div class="features-grid">
                    <?php foreach (($mod['features'] ?? []) as $feat): ?>
                        <div class="feature-bullet"><?php echo htmlspecialchars($feat); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</body>
</html>

