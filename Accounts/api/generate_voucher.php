<?php
// Get voucher parameters
$payee = isset($_GET['payee']) ? htmlspecialchars($_GET['payee']) : 'N/A';
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;
$mode = isset($_GET['mode']) ? htmlspecialchars($_GET['mode']) : 'Cash';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$description = isset($_GET['description']) ? htmlspecialchars($_GET['description']) : '';
$refNo = isset($_GET['refNo']) ? htmlspecialchars($_GET['refNo']) : 'PV-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

require_once 'invoice_utils.php';

$amountInWords = numberToWords($amount) . ' Rupees Only';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Voucher - <?php echo $refNo; ?></title>
    <style>
        :root {
            --primary: #0077c8;
            --text-dark: #1e293b;
            --text-grey: #64748b;
            --border: #cbd5e1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f1f5f9; padding: 20px; color: var(--text-dark); }

        .voucher-card {
            background: white;
            width: 210mm;
            min-height: 148mm; /* A5 Landscape style but in A4 container */
            margin: 0 auto;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border: 2px solid #000;
            border-top-width: 5px;
            border-top-color: var(--primary);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .logo-section img { height: 60px; margin-bottom: 8px; }
        .company-info h2 { font-size: 18px; font-weight: 800; color: var(--primary); }
        .company-info p { font-size: 11px; color: var(--text-grey); line-height: 1.4; }

        .voucher-type { text-align: right; }
        .voucher-type h1 { font-size: 24px; color: var(--primary); text-transform: uppercase; letter-spacing: 2px; }
        .ref-date { margin-top: 10px; font-size: 13px; font-weight: 600; color: var(--text-grey); }
        .ref-date span { color: var(--text-dark); margin-left: 5px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 20px;
            margin-bottom: 25px;
        }

        .input-group { margin-bottom: 15px; }
        .label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-grey); margin-bottom: 5px; display: block; }
        .value-box { 
            border-bottom: 1px solid var(--border); 
            padding: 8px 0; 
            font-size: 15px; 
            font-weight: 600; 
            min-height: 35px;
        }

        .amount-highlight {
            background: #f8fafc;
            border: 2px solid var(--primary);
            padding: 15px;
            text-align: center;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .amount-highlight .label { color: var(--primary); }
        .amount-highlight .total { font-size: 28px; font-weight: 800; color: var(--primary); }

        .mode-section {
            display: flex;
            gap: 25px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        .mode-item { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 14px; }
        .dot { width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--primary); position: relative; }
        .dot.active::after { content: ''; position: absolute; top: 2px; left: 2px; width: 6px; height: 6px; background: var(--primary); border-radius: 50%; }

        .desc-section { margin-bottom: 40px; }
        .desc-box { 
            border: 1px solid #e2e8f0; 
            padding: 15px; 
            border-radius: 8px; 
            min-height: 80px; 
            font-size: 14px; 
            line-height: 1.6;
            background: #fff;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            border-top: 1px dashed var(--border);
            padding-top: 20px;
        }
        .sig-col { text-align: center; width: 150px; }
        .sig-line { border-top: 1px solid var(--text-dark); margin-bottom: 8px; }
        .sig-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-grey); }

        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,119,200,0.3);
        }

        @media print {
            body { background: white; padding: 0; }
            .voucher-card { box-shadow: none; width: 100%; border-top: 3pt solid var(--primary); }
            .print-btn { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()">Print Voucher</button>

    <div class="voucher-card">
        <div class="header">
            <div class="company-info">
                <div class="logo-section">
                    <img src="../assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="TriShaKi Logo" onerror="this.src='https://via.placeholder.com/150x60/f8fafc/0077c8?text=TriShaKi'">
                </div>
                <h2>TRISHAKI TECHNOLOGIES PRIVATE LIMITED</h2>
                <p>F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank,<br>
                Tilakwadi, Belagavi, Karnataka - 590006<br>
                <strong>Phone:</strong> (+91) 9980681304 | <strong>Email:</strong> info@trishaki.com</p>
            </div>
            <div class="voucher-type">
                <h1>Payment Voucher</h1>
                <div class="ref-date">VOUCHER NO: <span><?php echo $refNo; ?></span></div>
                <div class="ref-date">DATE: <span><?php echo date('d-M-Y', strtotime($date)); ?></span></div>
            </div>
        </div>

        <div class="form-grid">
            <div class="main-details">
                <div class="input-group">
                    <span class="label">Paid To (Payee)</span>
                    <div class="value-box"><?php echo $payee; ?></div>
                </div>
                <div class="input-group">
                    <span class="label">Amount (in words)</span>
                    <div class="value-box"><?php echo $amountInWords; ?></div>
                </div>
            </div>
            <div class="amount-highlight">
                <span class="label">TOTAL AMOUNT</span>
                <div class="total">₹<?php echo number_format($amount, 2); ?></div>
            </div>
        </div>

        <span class="label">Payment Mode</span>
        <div class="mode-section">
            <div class="mode-item"><div class="dot active"></div> Cash</div>
        </div>

        <div class="desc-section">
            <span class="label">Particulars / Description</span>
            <div class="desc-box">
                <?php echo nl2br($description); ?>
            </div>
        </div>

        <div class="signatures">
            <div class="sig-col">
                <div class="sig-line"></div>
                <span class="sig-label">Receiver's Signature</span>
            </div>
            <div class="sig-col">
                <div class="sig-line"></div>
                <span class="sig-label">Authorised Signatory</span>
            </div>
        </div>
    </div>
</body>
</html>
