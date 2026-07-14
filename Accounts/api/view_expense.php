<?php
require_once 'config.php';
require_once 'invoice_utils.php';

$token = $_GET['token'] ?? '';
$expenseId = $_GET['id'] ?? '';

if ($token) {
    $decrypted = decryptToken($token);
    if (strpos($decrypted, 'EXP-') === 0) {
        $expenseId = intval(substr($decrypted, 4));
    }
}

if (!$expenseId) {
    die('Expense identifier required or invalid link');
}

try {
    $stmt = $conn->prepare("
        SELECT e.id, e.description, e.amount, e.date, e.created_at, e.attachment, 
               COALESCE(c.category_name, 'Other') as category, 
               COALESCE(p.mode_name, 'Cash') as payment_mode 
        FROM expenses e
        LEFT JOIN expenses_categories c ON e.category_id = c.id
        LEFT JOIN payment_modes p ON e.payment_mode_id = p.id
        WHERE e.id = ?
    ");
    $stmt->bind_param("i", $expenseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die('Expense not found');
    }
    
    $expense = $result->fetch_assoc();
    $amountInWords = numberToWords($expense['amount']) . ' Rupees Only';
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Share - EXP-<?php echo str_pad($expense['id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-light: #818cf8;
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --accent: #ef4444;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            width: 100%;
            max-width: 1100px;
        }

        /* Split Layout for Receipts */
        .receipt-layout {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 30px;
            background: var(--bg-card);
            border-radius: 24px;
            border: 1px solid var(--border);
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .receipt-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .receipt-header img {
            height: 40px;
            object-fit: contain;
        }

        .receipt-body {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 450px;
            max-height: 700px;
            overflow: hidden;
        }

        .receipt-img {
            max-width: 100%;
            max-height: 660px;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            transition: transform 0.2s;
        }

        .receipt-img:hover {
            transform: scale(1.02);
        }

        .non-image-attachment {
            text-align: center;
            padding: 40px;
        }

        .file-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }

        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.2s;
        }

        .btn-download:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        /* Sidebar details */
        .receipt-info-sidebar {
            background: rgba(15, 23, 42, 0.4);
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 25px;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .sidebar-header {
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sidebar-header h2 {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--primary-light);
        }

        .voucher-no {
            font-size: 13px;
            font-weight: 700;
            background: rgba(79, 70, 229, 0.2);
            color: var(--primary-light);
            padding: 4px 10px;
            border-radius: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
        }

        .info-value.amount {
            font-size: 28px;
            font-weight: 800;
            color: var(--accent);
        }

        .category-badge {
            background: rgba(248, 250, 252, 0.05);
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            display: inline-block;
            width: fit-content;
        }

        .info-value.desc {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 15px;
            border: 1px solid var(--border);
            font-size: 14px;
            line-height: 1.6;
            color: #cbd5e1;
        }

        /* Print Controls */
        .print-btn-inline {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            transition: all 0.2s;
        }

        .print-btn-inline:hover {
            background: var(--primary-light);
        }

        /* Fallback Voucher Style (When NO attachment uploaded) */
        .voucher-card {
            background: white;
            color: #0f172a;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-top: 6px solid var(--primary);
        }

        .voucher-card .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 25px;
            margin-bottom: 25px;
        }

        .voucher-card .company-info h2 {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }

        .voucher-card .company-info p {
            font-size: 11px;
            color: #64748b;
            line-height: 1.5;
            margin-top: 4px;
        }

        .voucher-card .voucher-type {
            text-align: right;
        }

        .voucher-card .voucher-type h1 {
            font-size: 20px;
            color: var(--primary);
            text-transform: uppercase;
        }

        .voucher-card .ref-date {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }

        .voucher-card .ref-date span {
            color: #0f172a;
            font-weight: 700;
        }

        .voucher-card .form-grid {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 20px;
            margin-bottom: 25px;
        }

        .voucher-card .label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 5px;
            display: block;
        }

        .voucher-card .value-box {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 0;
            font-size: 14px;
            font-weight: 600;
            min-height: 30px;
        }

        .voucher-card .amount-highlight {
            background: #f8fafc;
            border: 2px solid var(--primary);
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .voucher-card .amount-highlight .total {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
        }

        .voucher-card .details-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .voucher-card .desc-box {
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-size: 13px;
            background: #f8fafc;
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                color: black;
                padding: 0;
            }
            .receipt-layout {
                grid-template-columns: 1fr;
                border: none;
                box-shadow: none;
                padding: 0;
                background: white;
            }
            .receipt-info-sidebar, .receipt-header, .print-btn-inline {
                display: none !important;
            }
            .receipt-body {
                background: white;
                border: none;
                padding: 0;
                max-height: none;
                display: block;
            }
            .receipt-img {
                width: 100%;
                max-height: none;
                box-shadow: none;
            }
            .voucher-card {
                box-shadow: none;
                padding: 0;
                border-top: none;
            }
        }

        @media (max-width: 768px) {
            .receipt-layout {
                grid-template-columns: 1fr;
                gap: 20px;
                padding: 15px;
            }
            .receipt-body {
                min-height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($expense['attachment']): ?>
            <!-- RECEIPT LAYOUT (Primary) -->
            <div class="receipt-layout">
                <div class="receipt-main">
                    <div class="receipt-header">
                        <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="TriShaKi Logo" onerror="this.src='https://via.placeholder.com/150x50/f8fafc/6366f1?text=TriShaKi'">
                        <button class="print-btn-inline" onclick="window.print()">Print Receipt</button>
                    </div>
                    <div class="receipt-body">
                        <?php 
                        $file_ext = strtolower(pathinfo($expense['attachment'], PATHINFO_EXTENSION));
                        $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        if ($is_image): 
                        ?>
                            <img class="receipt-img" src="../<?php echo htmlspecialchars($expense['attachment']); ?>" alt="Expense Receipt">
                        <?php else: ?>
                            <div class="non-image-attachment">
                                <span class="file-icon">📄</span>
                                <p>This attachment (<?php echo strtoupper($file_ext); ?>) cannot be previewed directly.</p>
                                <a href="../<?php echo htmlspecialchars($expense['attachment']); ?>" class="btn-download" download>Download Attachment</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="receipt-info-sidebar">
                    <div class="sidebar-header">
                        <h2>EXPENSE DETAILS</h2>
                        <span class="voucher-no">NO: EXP-<?php echo str_pad($expense['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Total Amount</span>
                            <span class="info-value amount">₹<?php echo number_format($expense['amount'], 2); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Amount (In Words)</span>
                            <span class="info-value"><?php echo $amountInWords; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date</span>
                            <span class="info-value"><?php echo date('d-M-Y', strtotime($expense['date'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Expense Category</span>
                            <span class="info-value category-badge"><?php echo htmlspecialchars($expense['category']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Payment Mode</span>
                            <span class="info-value"><?php echo htmlspecialchars($expense['payment_mode']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Particulars / Description</span>
                            <span class="info-value desc"><?php echo nl2br(htmlspecialchars($expense['description'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- VOUCHER LAYOUT (Fallback when no attachment exists) -->
            <button class="print-btn-inline" style="position: fixed; top: 20px; right: 20px; z-index: 10;" onclick="window.print()">Print Voucher</button>
            <div class="voucher-card">
                <div class="header">
                    <div class="company-info">
                        <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" style="height: 40px; margin-bottom: 10px;" alt="TriShaKi Logo" onerror="this.src='https://via.placeholder.com/150x50/f8fafc/6366f1?text=TriShaKi'">
                        <h2>TRISHAKI PRIVATE LIMITED</h2>
                        <p>F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank,<br>
                        Tilakwadi, Belagavi, Karnataka - 590006<br>
                        <strong>Phone:</strong> (+91) 9980681304 | <strong>Email:</strong> info@trishaki.com</p>
                    </div>
                    <div class="voucher-type">
                        <h1>Expense Voucher</h1>
                        <div class="ref-date">VOUCHER NO: <span>EXP-<?php echo str_pad($expense['id'], 4, '0', STR_PAD_LEFT); ?></span></div>
                        <div class="ref-date">DATE: <span><?php echo date('d-M-Y', strtotime($expense['date'])); ?></span></div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="main-details">
                        <div class="input-group">
                            <span class="label">Amount (in words)</span>
                            <div class="value-box"><?php echo $amountInWords; ?></div>
                        </div>
                    </div>
                    <div class="amount-highlight">
                        <span class="label">TOTAL AMOUNT</span>
                        <div class="total">₹<?php echo number_format($expense['amount'], 2); ?></div>
                    </div>
                </div>

                <div class="details-row">
                    <div class="input-group">
                        <span class="label">Expense Category</span>
                        <div class="value-box"><?php echo htmlspecialchars($expense['category']); ?></div>
                    </div>
                    <div class="input-group">
                        <span class="label">Payment Mode</span>
                        <div class="value-box"><?php echo htmlspecialchars($expense['payment_mode']); ?></div>
                    </div>
                </div>

                <div class="desc-section">
                    <span class="label">Particulars / Description</span>
                    <div class="desc-box">
                        <?php echo nl2br(htmlspecialchars($expense['description'])); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
