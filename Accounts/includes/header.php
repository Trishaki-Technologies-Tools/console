<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.html");
    exit;
}

// Load 2FA Configuration
require_once '../2fa_config.php';

// 2FA Check for Accounts Module
if (defined('ENABLE_2FA') && ENABLE_2FA && (!isset($_SESSION['accounts_2fa_verified']) || $_SESSION['accounts_2fa_verified'] !== true)) {

    // Check if 2FA secret exists (Specific for Accounts)
    $secretFile = 'accounts_secret.txt';
    $isSetup = !file_exists($secretFile);
    $qrCodeUrl = '';
    $secret = '';

    if ($isSetup) {
        require_once '../GoogleAuthenticator.php';
        $g2fa = new GoogleAuthenticator();
        $secret = $g2fa->createSecret();
        // unique name for the app
        $qrCodeUrl = $g2fa->getQRCodeGoogleUrl('TrishakiAccounts', $secret, 'Trishaki Technologies');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accounts Security - Trishaki</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: #0f172a;
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
            }

            .auth-container {
                background: rgba(30, 41, 59, 0.7);
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                padding: 40px;
                border-radius: 16px;
                text-align: center;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            }

            .icon {
                font-size: 3rem;
                margin-bottom: 20px;
            }

            h2 {
                margin: 0 0 10px;
                font-weight: 600;
            }

            p {
                color: #94a3b8;
                margin-bottom: 30px;
            }

            input {
                width: 100%;
                padding: 15px;
                background: rgba(15, 23, 42, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 8px;
                color: white;
                font-size: 1.2rem;
                text-align: center;
                letter-spacing: 5px;
                margin-bottom: 20px;
                outline: none;
                transition: border-color 0.3s;
            }

            input:focus {
                border-color: #6366f1;
            }

            button {
                width: 100%;
                padding: 15px;
                background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
                border: none;
                border-radius: 8px;
                color: white;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.2s;
            }

            button:hover {
                transform: translateY(-2px);
            }

            button:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }

            .error {
                color: #ef4444;
                font-size: 0.9rem;
                margin-top: 15px;
                display: none;
            }

            .qr-code {
                background: white;
                padding: 10px;
                border-radius: 8px;
                margin-bottom: 20px;
            }

            .secret-key {
                font-family: monospace;
                color: #a855f7;
                margin-bottom: 20px;
                display: block;
                letter-spacing: 2px;
            }
        </style>
    </head>

    <body>
    <div class="container">
        <div class="auth-container">
            <div class="icon">ðŸ”?</div>

            <?php if ($isSetup): ?>
                <h2>Setup 2FA</h2>
                <p>Scan this QR code with Google Authenticator App</p>
                <div class="qr-code">
                    <img src="<?php echo $qrCodeUrl; ?>" alt="QR Code" width="200" height="200">
                </div>
                <span class="secret-key"><?php echo $secret; ?></span>
                <p>Then enter the 6-digit code below</p>
            <?php else: ?>
                <h2>Security Verification</h2>
                <p>Please enter your 2FA code to access Accounts</p>
            <?php endif; ?>

            <input type="text" id="code" maxlength="6" placeholder="000000" autofocus>
            <button onclick="verify()" id="btn"><?php echo $isSetup ? 'Setup & Verify' : 'Verify Access'; ?></button>
            <div id="error" class="error">Invalid Code</div>
        </div>

        <script>
            const isSetup = <?php echo $isSetup ? 'true' : 'false'; ?>;
            const secret = "<?php echo $secret; ?>";

            async function verify() {
                const code = document.getElementById('code').value;
                const btn = document.getElementById('btn');
                const err = document.getElementById('error');

                if (code.length < 6) return;

                btn.disabled = true;
                btn.textContent = 'Verifying...';
                err.style.display = 'none';

                const payload = { code: code };
                if (isSetup) {
                    payload.action = 'setup';
                    payload.secret = secret;
                }

                try {
                    const res = await fetch('api/verify_2fa.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();

                    if (data.success) {
                        location.reload();
                    } else {
                        err.textContent = data.message || 'Invalid Code';
                        err.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = isSetup ? 'Setup & Verify' : 'Verify Access';
                    }
                } catch (e) {
                    err.textContent = 'Connection Error';
                    err.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = isSetup ? 'Setup & Verify' : 'Verify Access';
                }
            }

            // Auto submit on enter
            document.getElementById('code').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') verify();
            });
        </script>
    <datalist id="clientDatalist"></datalist>
    
</body>

    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>TriShaKi Technologies - Finance Dashboard</title>
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        /* Fallback Layout Spacing Fixes */
        .page-content { padding: 32px 40px !important; }
        .stats-row-small { display: flex !important; flex-wrap: wrap !important; gap: 20px !important; margin-bottom: 24px !important; }
        .stat-card-small { flex: 1 1 220px !important; min-width: 220px !important; }
        .dashboard-section { margin-bottom: 35px !important; }
        .records-section { margin-top: 24px !important; margin-bottom: 24px !important; }
    </style>
    <style>
        .dropdown-option {
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .dropdown-option:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #6366f1 !important;
        }
    </style>
</head>

<body>
    <div class="container">