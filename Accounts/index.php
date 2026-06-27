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
            .icon { font-size: 3rem; margin-bottom: 20px; }
            h2 { margin: 0 0 10px; font-weight: 600; }
            p { color: #94a3b8; margin-bottom: 30px; }
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
            input:focus { border-color: #6366f1; }
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
            button:hover { transform: translateY(-2px); }
            button:disabled { opacity: 0.7; cursor: not-allowed; }
            .error { color: #ef4444; font-size: 0.9rem; margin-top: 15px; display: none; }
            .qr-code { background: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
            .secret-key { font-family: monospace; color: #a855f7; margin-bottom: 20px; display: block; letter-spacing: 2px; }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <div class="icon">🔐</div>
            
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
                
                if(code.length < 6) return;

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
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    
                    if(data.success) {
                        location.reload();
                    } else {
                        err.textContent = data.message || 'Invalid Code';
                        err.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = isSetup ? 'Setup & Verify' : 'Verify Access';
                    }
                } catch(e) {
                    err.textContent = 'Connection Error';
                    err.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = isSetup ? 'Setup & Verify' : 'Verify Access';
                }
            }

            // Auto submit on enter
            document.getElementById('code').addEventListener('keypress', function(e) {
                if(e.key === 'Enter') verify();
            });
        </script>
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
    <link rel="stylesheet" href="css/style.css">
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
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h2 class="logo-full">TriShaKi Technologies</h2>
                <h2 class="logo-collapsed">TS</h2>
                <p class="company-subtitle">Private Limited</p>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-page="dashboard" title="Dashboard">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg></span>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item" data-page="transactions" title="Transactions">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></span>
                    <span>Transactions</span>
                </a>
                <a href="#" class="nav-item" data-page="salary-logs" title="Salary Logs">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></span>
                    <span>Salary Logs</span>
                </a>
                <a href="#" class="nav-item" data-page="loans" title="Loans">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                    <span>Loans</span>
                </a>
                <a href="#" class="nav-item" data-page="invoices" title="Invoices">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg></span>
                    <span>Invoices</span>
                </a>
                <a href="#" class="nav-item" data-page="voucher" title="Vouchers">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="16" y1="8" x2="16" y2="16"></line><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="8" x2="8" y2="16"></line></svg></span>
                    <span>Vouchers</span>
                </a>
                <a href="#" class="nav-item" data-page="clients" title="Clients">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                    <span>Clients</span>
                </a>
                <a href="#" class="nav-item" data-page="quotations" title="Quotations">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg></span>
                    <span>Quotations</span>
                </a>
                <a href="#" class="nav-item" data-page="audit-logs" title="Audit Logs">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span>
                    <span>Audit Logs</span>
                </a>
                <a href="#" class="nav-item" data-page="settings" title="Settings">
                    <span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></span>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <button class="btn-sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" style="background: #ffffff; border: 1px solid #d1d5db; font-size: 18px; color: #111827; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 12px;">
                        <span class="icon">☰</span>
                    </button>
                    <div>
                        <h1 id="page-title">Dashboard</h1>
                        <p class="welcome-text">Overview of your financial records</p>
                    </div>
                </div>
                <div class="user-info">
                    <button class="btn-logout" onclick="logout()">Sign Out</button>
                </div>
            </header>

            <div class="content" id="content-area">
                <!-- Dashboard Content -->
                <div class="page-content" id="dashboard-page">
                    <!-- Balances Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="22" width="20" height="2"></rect><path d="M4 22V10l8-6 8 6v12M18 22H6M12 10v12M9 14v8M15 14v8"></path></svg>
                            Balances Section
                        </h3>
                        <div class="stats-row-small" id="balances-grid">
                            <!-- Total Available Balance Card -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="balance">₹0</div>
                                    <div class="stat-label-small">Total Available Balance</div>
                                </div>
                            </div>
                            <!-- Placeholder for Payment Method Cards -->
                            <div id="payment-balances-row" style="display: contents;">
                                <!-- Dynamically loaded from JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Income Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></svg>
                            Income Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- This Month Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-month-income">₹0</div>
                                    <div class="stat-label-small">This Month Income</div>
                                </div>
                            </div>
                            <!-- Last Month Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="last-month-income">₹0</div>
                                    <div class="stat-label-small">Last Month Income</div>
                                </div>
                            </div>
                            <!-- This Year Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-year-income">₹0</div>
                                    <div class="stat-label-small">This Year Income</div>
                                </div>
                            </div>
                            <!-- Total Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-income">₹0</div>
                                    <div class="stat-label-small">Total Income</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                            Expense Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- This Month Expense -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-month-expense">₹0</div>
                                    <div class="stat-label-small">This Month Expense</div>
                                </div>
                            </div>
                            <!-- Last Month Expense -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="last-month-expense">₹0</div>
                                    <div class="stat-label-small">Last Month Expense</div>
                                </div>
                            </div>
                            <!-- This Year Expense -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-year-expense">₹0</div>
                                    <div class="stat-label-small">This Year Expense</div>
                                </div>
                            </div>
                            <!-- Total Expenses -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-expenses">₹0</div>
                                    <div class="stat-label-small">Total Expenses</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profit/Loss Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                            Profit/Loss Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- This Month Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-month-profit">₹0</div>
                                    <div class="stat-label-small">This Month Profit/Loss</div>
                                </div>
                            </div>
                            <!-- Last Month Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="last-month-profit">₹0</div>
                                    <div class="stat-label-small">Last Month Profit/Loss</div>
                                </div>
                            </div>
                            <!-- This Year Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-year-profit">₹0</div>
                                    <div class="stat-label-small">This Year Profit/Loss</div>
                                </div>
                            </div>
                            <!-- Overall Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="overall-profit">₹0</div>
                                    <div class="stat-label-small">Overall Profit/Loss</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loans Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                            Loans Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- Active Loan Amount -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="2" ry="2" /><line x1="12" y1="18" x2="12.01" y2="18" /><rect x="6" y="6" width="12" height="8" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="active-loan-amount">₹0</div>
                                    <div class="stat-label-small">Active Loan Amount</div>
                                </div>
                            </div>
                            <!-- Interest Paid Till Date -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23" /><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="interest-paid-total">₹0</div>
                                    <div class="stat-label-small">Interest Paid Till Date</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Business Operations Stats -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /></svg>
                            Business Operations Stats
                        </h3>
                        <div class="stats-row-small">
                            <!-- Invoices Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-invoices-count">0</div>
                                    <div class="stat-label-small">Invoices Total Count</div>
                                </div>
                            </div>
                            <!-- Vouchers Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="16" y1="2" x2="16" y2="4"></line><line x1="8" y1="2" x2="8" y2="4"></line></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-vouchers-count">0</div>
                                    <div class="stat-label-small">Vouchers Total Count</div>
                                </div>
                            </div>
                            <!-- Clients Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-clients-count">0</div>
                                    <div class="stat-label-small">Clients Total Count</div>
                                </div>
                            </div>
                            <!-- Quotations Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-quotations-count">0</div>
                                    <div class="stat-label-small">Quotations Total Count</div>
                                </div>
                            </div>
                            <!-- Employees Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-employees-count">0</div>
                                    <div class="stat-label-small">Employees Total Count</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>                <!-- Transactions Content -->
                <div class="page-content hidden" id="transactions-page">
                    <div class="section-header" style="margin-bottom: 20px;">
                        <div>
                            <h2>Financial Transactions Ledger</h2>
                            <p class="company-subtitle">Unified Income & Expenses Ledger</p>
                        </div>
                        <div class="filter-row" style="gap: 12px;">
                            <!-- Tab Options -->
                            <div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px;">
                                <button id="txn-tab-income" class="ledger-tab-btn active" onclick="switchLedgerTab('income')">Incomes</button>
                                <button id="txn-tab-expense" class="ledger-tab-btn" onclick="switchLedgerTab('expense')">Expenses</button>
                            </div>
                            <button class="btn-primary" id="btn-add-income" onclick="openAddIncomeModal()">+ Add Income</button>
                            <button class="btn-primary" id="btn-add-expense" onclick="openAddExpenseModal()" style="display: none; background: var(--danger);">+ Add Expense</button>
                        </div>
                    </div>

                    <!-- Filter Controls Section -->
                    <div class="records-section" style="margin-bottom: 20px; padding: 16px; border: 1px solid var(--border-light); border-radius: 12px; background: var(--bg-card);">
                        <div style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
                            <!-- Left: Search and Filters -->
                            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                                <input type="text" id="txn-search" class="form-input" placeholder="Search description, category..." oninput="onTxnFilterChange()" style="max-width: 250px; min-width: 200px;">
                                <div class="ledger-period-filters" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-left: 10px;">
                                    <a href="#" class="period-filter-link active" data-period="this-month" onclick="selectPeriod(event, 'this-month')">This Month</a>
                                    <a href="#" class="period-filter-link" data-period="last-month" onclick="selectPeriod(event, 'last-month')">Last Month</a>
                                    <a href="#" class="period-filter-link" data-period="this-year" onclick="selectPeriod(event, 'this-year')">This Year</a>
                                    <a href="#" class="period-filter-link" data-period="total" onclick="selectPeriod(event, 'total')">Total</a>
                                    <a href="#" class="period-filter-link" data-period="specific-date" onclick="selectPeriod(event, 'specific-date')">Specific Date</a>
                                    <a href="#" class="period-filter-link" data-period="date-range" onclick="selectPeriod(event, 'date-range')">Date Range</a>
                                </div>

                                <!-- Specific Date Picker (Hidden by default) -->
                                <input type="date" id="txn-specific-date" class="form-input" onchange="onTxnFilterChange()" style="display: none; max-width: 150px;">

                                <!-- Date Range Pickers (Hidden by default) -->
                                <div id="txn-date-range-container" style="display: none; align-items: center; gap: 8px;">
                                    <input type="date" id="txn-start-date" class="form-input" onchange="onTxnFilterChange()" style="max-width: 140px;">
                                    <span style="color: #64748b; font-size: 14px;">to</span>
                                    <input type="date" id="txn-end-date" class="form-input" onchange="onTxnFilterChange()" style="max-width: 140px;">
                                </div>
                            </div>

                            <!-- Right: Category Manage -->
                            <div style="display: flex; gap: 10px;">
                                <button id="btn-manage-income-cats" class="btn-secondary" onclick="openCategoryModal()">📊 Manage Categories</button>
                                <button id="btn-manage-expense-cats" class="btn-secondary" onclick="openExpenseCategoryModal()" style="display: none;">📊 Manage Categories</button>
                            </div>
                        </div>
                    </div>

                    <!-- Ledger Totals Bar -->
                    <div class="stats-row-small" style="margin-bottom: 20px;">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">💰</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="txn-total-sum">₹0.00</div>
                                <div class="stat-label-small" id="txn-total-label">Total Incomes Value</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">🔢</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="txn-total-count">0</div>
                                <div class="stat-label-small" id="txn-count-label">Incomes Count</div>
                            </div>
                        </div>
                    </div>

                    <!-- List container -->
                    <div class="records-section">
                        <div class="table-responsive" id="txn-table-container">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading transactions...</p>
                        </div>
                    </div>
                </div>

                <!-- Salary Logs Content -->
                <div class="page-content hidden" id="salary-logs-page">
                    <div class="section-header">
                        <div>
                            <h2>Salary Logs & Payroll</h2>
                            <p class="company-subtitle">Manage employee salaries and payslips</p>
                        </div>
                        <div class="filter-row">
                            <button class="btn-secondary" onclick="openEmployeeModal()">👥 Manage Employees</button>
                            <button class="btn-primary" onclick="showAddSalaryModal()">💰 Pay Salary</button>
                        </div>
                    </div>

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">📅</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="salary-paid-this-month">₹0</div>
                                <div class="stat-label-small">This Month Payroll</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">₹</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-salary-paid">₹0</div>
                                <div class="stat-label-small">Total Payroll Paid</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">👥</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-employees-paid">0</div>
                                <div class="stat-label-small">Employees Count</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-orange">🗓️</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="salary-paid-last-month">₹0</div>
                                <div class="stat-label-small">Last Month Payroll</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="salary-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading salary records...</p>
                        </div>
                    </div>
                </div>

                <!-- Loans Content -->
                <div class="page-content hidden" id="loans-page">
                    <div class="section-header">
                        <div>
                            <h2>Liability & Loans Tracker</h2>
                            <p class="company-subtitle">Monitor liabilities, interest and principal payments</p>
                        </div>
                        <button class="btn-primary" onclick="showAddLoanModal()">+ Add Loan</button>
                    </div>

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">💰</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-loan-taken">₹0</div>
                                <div class="stat-label-small">Total Loan Taken</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-red">📋</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="active-loan-amount">₹0</div>
                                <div class="stat-label-small">Active Loan Amount</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">↩️</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-loan-paid-back">₹0</div>
                                <div class="stat-label-small">Total Paid Back</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">📈</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-interest-paid">₹0</div>
                                <div class="stat-label-small">Total Interest Paid</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="loans-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading loans...</p>
                        </div>
                    </div>
                </div>

                <!-- Invoices Content -->
                <div class="page-content hidden" id="invoices-page">
                    <div class="section-header">
                        <div>
                            <h2>GST & Non-GST Invoices</h2>
                            <p class="company-subtitle">Manage client billings, invoice states and print layouts</p>
                        </div>
                        <div class="filter-row">
                            <button class="btn-secondary" onclick="triggerCSVImport()">📂 Bulk Import (CSV)</button>
                            <button class="btn-danger" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.3);" onclick="clearAllInvoices()">🗑️ Clear All</button>
                            <div class="generate-invoice-container" style="position: relative; display: inline-block;">
                                <button class="btn-primary" onclick="showInvoiceTypeSelection()">+ Generate Invoice</button>
                                <!-- Invoice Type Selection Dropdown -->
                                <div id="invoiceTypeSelectionDiv" style="display: none; position: absolute; right: 0; top: 110%; background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 8px; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.5); grid-template-columns: 1fr; gap: 8px; min-width: 180px;">
                                    <button class="btn-secondary dropdown-option" style="text-align: left; padding: 10px; width: 100%; border: none; background: transparent; color: white;" onclick="selectInvoiceType('gst')">📝 GST Invoice (18%)</button>
                                    <button class="btn-secondary dropdown-option" style="text-align: left; padding: 10px; width: 100%; border: none; background: transparent; color: white;" onclick="selectInvoiceType('non-gst')">📄 Non-GST Invoice</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <input type="file" id="csv-file-input" accept=".csv" style="display: none;" onchange="importInvoicesCSV(event)">

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">🧾</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-amount">₹0.00</div>
                                <div class="stat-label-small">Total Amount Billed</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">💵</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-paid">₹0.00</div>
                                <div class="stat-label-small">Total Cumulative Paid</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-cyan">📋</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-count">0</div>
                                <div class="stat-label-small">Total Invoices Issued</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section" id="invoice-list-container">
                        <div class="records-header">
                            <h3>Issued Invoices</h3>
                            <div class="filter-row">
                                <input type="text" id="invoice-search" class="form-input" placeholder="Search by name/number..." onkeyup="searchInvoices()" style="max-width: 250px;">
                            </div>
                        </div>
                        <div class="table-responsive" id="invoice-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading invoices...</p>
                        </div>
                    </div>

                    <!-- Create Invoice Section (Hidden by default, displayed inline when clicking Generate) -->
                    <div class="records-section hidden" id="invoice-form-container">
                        <div class="records-header">
                            <h3 id="invoice-form-title">Create New Invoice</h3>
                            <button class="btn-secondary" onclick="closeInvoiceForm()">Back to List</button>
                        </div>
                        <form id="invoiceForm" onsubmit="saveInvoice(event)">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Client / Billing Name <span class="required">*</span></label>
                                    <input type="text" id="billToName" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <input type="text" id="phone" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" id="email" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GST Number</label>
                                    <input type="text" id="gstNumber" class="form-input" placeholder="e.g. 29ABCDE1234F1Z5">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Billing Address</label>
                                    <textarea id="address" class="form-input" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Date <span class="required">*</span></label>
                                    <input type="date" id="invoiceDate" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Type <span class="required">*</span></label>
                                    <select id="invoiceType" class="form-input" onchange="calculateInvoiceSummary()">
                                        <option value="non-gst">Non-GST</option>
                                        <option value="gst">GST (18%)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Number (Auto/Custom)</label>
                                    <input type="text" id="invoiceNo" class="form-input" placeholder="Keep empty for auto-generate">
                                </div>
                            </div>

                            <h4 style="margin-top: 30px; margin-bottom: 15px; color: #fff;">Invoice Items</h4>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Qty</th>
                                            <th>Rate (₹)</th>
                                            <th>GST Rate (%)</th>
                                            <th>Amount (₹)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoiceItemsTableBody">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 15px; margin-bottom: 30px;">
                                <button type="button" class="btn-secondary" onclick="addInvoiceItemRow()">+ Add Item</button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                                <div>
                                    <div class="form-group">
                                        <label class="form-label">Payment Received (₹)</label>
                                        <input type="number" id="amtPaid" class="form-input" value="0" step="0.01" oninput="calculateInvoiceSummary()">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Cumulative Total Paid (Read-Only)</label>
                                        <input type="number" id="cumulativePaid" class="form-input" value="0" readonly>
                                    </div>
                                </div>
                                <div style="text-align: right; color: var(--text-muted); font-size: 15px; font-weight: 500;">
                                    <p>Subtotal: <span id="invSubtotal" style="color: #fff; font-weight: 600;">₹0.00</span></p>
                                    <p id="invGstRow">CGST (9%) + SGST (9%): <span id="invGst" style="color: #fff; font-weight: 600;">₹0.00</span></p>
                                    <h3 style="font-size: 22px; margin-top: 10px; color: #fff;">Total Payable: <span id="invTotalPayable">₹0.00</span></h3>
                                    <h3 style="font-size: 18px; margin-top: 5px; color: var(--danger);">Balance Due: <span id="invBalanceDue">₹0.00</span></h3>
                                </div>
                            </div>

                            <div class="modal-actions" style="margin-top: 30px; justify-content: flex-end;">
                                <button type="button" class="btn-cancel" onclick="closeInvoiceForm()">Cancel</button>
                                <button type="submit" class="btn-save">Save & Issue Invoice</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Voucher Content -->
                <div class="page-content hidden" id="voucher-page">
                    <div class="section-header">
                        <div>
                            <h2>Payment Vouchers</h2>
                            <p class="company-subtitle">Manage payout vouchers, payees and printing formats</p>
                        </div>
                        <button class="btn-primary" onclick="showAddVoucherModal()">+ Generate New Voucher</button>
                    </div>

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">📋</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-voucher-count">0</div>
                                <div class="stat-label-small">Total Vouchers</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">💵</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-voucher-amount">₹0</div>
                                <div class="stat-label-small">Total Disbursed</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="voucher-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading vouchers...</p>
                        </div>
                    </div>
                </div>

                <!-- Clients Content -->
                <div class="page-content hidden" id="clients-page">
                    <div class="section-header">
                        <div>
                            <h2>Clients CRM & Directory</h2>
                            <p class="company-subtitle">View active clients, invoice history and GST information</p>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="clients-list-container">
                            <!-- Populated dynamically by clients load -->
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading clients list...</p>
                        </div>
                    </div>
                </div>

                <!-- Quotations Content -->
                <div class="page-content hidden" id="quotations-page">
                    <div class="section-header">
                        <div>
                            <h2>Corporate Quotations</h2>
                            <p class="company-subtitle">Draft and issue quotations to prospects</p>
                        </div>
                        <button class="btn-primary" onclick="openQuotationModal()">+ Create Quotation</button>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="quotations-list-container">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading quotations...</p>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Content -->
                <div class="page-content hidden" id="audit-logs-page">
                    <div class="section-header">
                        <div>
                            <h2>Security Audit Trail</h2>
                            <p class="company-subtitle">Centralized database audit logs and operational history</p>
                        </div>
                        <input type="text" id="audit-search" class="form-input" placeholder="Search logs..." onkeyup="filterAuditLogs()" style="max-width: 250px;">
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="audit-logs-container">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading audit logs...</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="page-content hidden" id="settings-page">
                    <div class="section-header">
                        <div>
                            <h2>Global Settings</h2>
                            <p class="company-subtitle">Configure company details, branding and module configurations</p>
                        </div>
                    </div>

                    <div class="records-section" style="max-width: 800px;">
                        <form id="settingsForm" onsubmit="saveGlobalSettings(event)">
                            <h3 style="color: #fff; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Company Information</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Company Name <span class="required">*</span></label>
                                    <input type="text" id="setCompanyName" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GSTIN / Tax ID</label>
                                    <input type="text" id="setCompanyGst" class="form-input">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" id="setCompanyPhone" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" id="setCompanyEmail" class="form-input">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company Address</label>
                                <textarea id="setCompanyAddress" class="form-input" rows="3"></textarea>
                            </div>

                            <h3 style="color: #fff; margin-top: 30px; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Security Configurations</h3>
                            <div class="form-group" style="display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid var(--border-light); margin-bottom: 20px;">
                                <input type="checkbox" id="set2FA" style="width: 20px; height: 20px; cursor: pointer;">
                                <div>
                                    <label class="form-label" style="margin-bottom: 0; cursor: pointer;" for="set2FA">Enable Multi-Factor Authentication (2FA)</label>
                                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Enforce 6-digit Google Authenticator code checks for securing accounting operations.</p>
                                </div>
                            </div>

                            <h3 style="color: #fff; margin-top: 30px; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">Payment Settings</h3>
                            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid var(--border-light); margin-bottom: 20px;">
                                <div>
                                    <label class="form-label" style="margin-bottom: 0;">Payment Methods Management</label>
                                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Add, remove, and manage custom payment methods used across the system.</p>
                                </div>
                                <button type="button" class="btn-secondary" onclick="openPaymentModesModal()">💳 Manage Payment Methods</button>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" class="btn-primary">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Category Management Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Income Categories</h3>
                <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Category</label>
                    <div class="category-form">
                        <input type="text" id="newCategoryInput" class="category-input" placeholder="Category Name">
                        <button class="btn-add-category" onclick="addCategory()">+ Add</button>
                    </div>
                </div>
                <div class="category-list" id="categoryList"></div>
            </div>
        </div>
    </div>

    <!-- Expense Category Management Modal -->
    <div id="expenseCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Expense Categories</h3>
                <button class="modal-close" onclick="closeExpenseCategoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Category</label>
                    <div class="category-form">
                        <input type="text" id="newExpenseCategoryInput" class="category-input"
                            placeholder="Category Name">
                        <button class="btn-add-category" onclick="addExpenseCategory()">+ Add</button>
                    </div>
                </div>
                <div class="category-list" id="expenseCategoryList"></div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Management Modal -->
    <div id="paymentModesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Payment Methods</h3>
                <button class="modal-close" onclick="closePaymentModesModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Payment Method</label>
                    <div class="category-form">
                        <input type="text" id="newPaymentModeInput" class="category-input" placeholder="Payment Method Name">
                        <button class="btn-add-category" onclick="addPaymentMode()">+ Add</button>
                    </div>
                </div>
                <div class="category-list" id="paymentModeList"></div>
            </div>
        </div>
    </div>

    <!-- Employee Management Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Employees</h3>
                <button class="modal-close" onclick="closeEmployeeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Employee</label>
                    <div class="category-form" style="display: flex; flex-direction: column; gap: 10px;">
                        <input type="text" id="newEmployeeNameInput" class="category-input" placeholder="Employee Name"
                            style="width: 100%;">
                        <button class="btn-add-category" onclick="addEmployee()" style="width: 100%;">+ Add
                            Employee</button>
                    </div>
                </div>
                <div class="category-list" id="employeeList"></div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Add Expense</h3>
                <button class="modal-close" onclick="closeAddExpenseModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="expenseForm" onsubmit="submitExpense(event)" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Date & Time <span class="required">*</span></label>
                            <input type="datetime-local" id="expenseDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="expenseAmount" class="form-input" placeholder="0.00" step="0.01"
                                min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="expenseCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="expenseDescription" class="form-input" placeholder="Enter description"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="expensePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="expenseAttachment" class="form-input">
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddExpenseModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Income Modal -->
    <div id="addIncomeModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Add Income</h3>
                <button class="modal-close" onclick="closeAddIncomeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="incomeForm" onsubmit="submitIncome(event)" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Date & Time <span class="required">*</span></label>
                            <input type="datetime-local" id="incomeDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="incomeAmount" class="form-input" placeholder="0.00" step="0.01" min="0"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="incomeCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="incomeDescription" class="form-input" placeholder="Enter description"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="incomePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="incomeAttachment" class="form-input">
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddIncomeModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Salary Modal -->
    <div id="addSalaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pay Salary</h3>
                <button class="modal-close" onclick="closeAddSalaryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="salaryForm" onsubmit="submitSalary(event)">
                    <div class="form-group">
                        <label class="form-label">Employee Name <span class="required">*</span></label>
                        <select id="salaryEmployeeSelect" class="form-select" required>
                            <option value="">Select Employee</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Month <span class="required">*</span></label>
                        <input type="month" id="salaryMonth" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="salaryPaymentDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount <span class="required">*</span></label>
                        <input type="number" id="salaryAmount" class="form-input" placeholder="0.00" step="0.01" min="0"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode <span class="required">*</span></label>
                        <select id="salaryPaymentMode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddSalaryModal()">Cancel</button>
                        <button type="submit" class="btn-save">Pay & Generate Slip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payslip View Modal -->
    <div id="payslipViewModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Salary Payment Receipt</h3>
                <button class="modal-close" onclick="closePayslipViewModal()">&times;</button>
            </div>
            <div class="modal-body" id="payslipViewContent" style="padding: 20px;">
            </div>
            <div class="modal-actions" style="padding: 0 20px 20px;">
                <button class="btn-cancel" onclick="closePayslipViewModal()">Close</button>
                <button class="btn-primary" onclick="printPayslipModal()">🖨️ Print</button>
            </div>
        </div>
    </div>

    <!-- Edit Salary Modal -->
    <div id="editSalaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Salary Log</h3>
                <button class="modal-close" onclick="closeEditSalaryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editSalaryForm" onsubmit="submitEditSalary(event)">
                    <input type="hidden" id="editSalaryId">
                    <div class="form-group">
                        <label class="form-label">Employee Name <span class="required">*</span></label>
                        <input type="text" id="editSalaryEmployeeName" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role/Designation</label>
                        <input type="text" id="editSalaryRole" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Month <span class="required">*</span></label>
                        <input type="month" id="editSalaryMonth" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="editSalaryPaymentDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount <span class="required">*</span></label>
                        <input type="number" id="editSalaryAmount" class="form-input" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode</label>
                        <select id="editSalaryPaymentMode" class="form-select">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="editSalaryStatus" class="form-select">
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditSalaryModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Loan Modal -->
    <div id="addLoanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Loan</h3>
                <button class="modal-close" onclick="closeAddLoanModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="loanForm" onsubmit="submitLoan(event)">
                    <div class="form-group">
                        <label class="form-label">Source Type <span class="required">*</span></label>
                        <select id="loanSourceType" class="form-select" required onchange="updateLoanSourceLabel()">
                            <option value="">Select Source</option>
                            <option value="Person">Person</option>
                            <option value="Bank">Bank</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" id="loanCreditorLabel">Name <span class="required">*</span></label>
                        <input type="text" id="loanCreditor" class="form-input" placeholder="Enter name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Total Loan Amount <span class="required">*</span></label>
                        <input type="number" id="loanAmount" class="form-input" placeholder="0.00" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Charges Deducted (Optional)</label>
                        <input type="number" id="loanCharges" class="form-input" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Interest Rate (% Annually)</label>
                        <input type="number" id="loanInterest" class="form-input" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" id="loanStartDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode <span class="required">*</span></label>
                        <select id="loanPaymentMode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="loanDescription" class="form-input" rows="2" placeholder="Additional details"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddLoanModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Loan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Loan Modal -->
    <div id="editLoanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Loan</h3>
                <button class="modal-close" onclick="closeEditLoanModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editLoanForm" onsubmit="submitEditLoan(event)">
                    <input type="hidden" id="editLoanId">
                    <div class="form-group">
                        <label class="form-label">Creditor Name <span class="required">*</span></label>
                        <input type="text" id="editLoanCreditor" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Principal Amount <span class="required">*</span></label>
                        <input type="number" id="editLoanAmount" class="form-input" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Interest Rate (%)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="editLoanInterest" class="form-input" step="0.01" min="0"
                                style="flex: 1;">
                            <div style="display: flex; gap: 5px;">
                                <label><input type="radio" name="editInterestType" value="Monthly"> Monthly</label>
                                <label><input type="radio" name="editInterestType" value="Annual"> Annual</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" id="editLoanStartDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="editLoanStatus" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="editLoanDescription" class="form-input" rows="3"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditLoanModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Loan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pay Interest Modal -->
    <div id="payInterestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pay Interest</h3>
                <button class="modal-close" onclick="closePayInterestModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="payInterestForm" onsubmit="submitInterestPayment(event)">
                    <input type="hidden" id="payInterestLoanId">
                    <p id="payInterestText" style="margin-bottom: 20px; color: #64748b;">Record interest payment for
                        this month.</p>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="payInterestDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Interest Amount (Editable)</label>
                        <input type="number" id="payInterestAmountDisplay" class="form-input" step="0.01" min="0"
                            required style="font-weight: bold; color: #0f172a;">
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closePayInterestModal()">Cancel</button>
                        <button type="submit" class="btn-save">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Repay Loan Modal -->
    <div id="repayLoanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Repay Principal</h3>
                <button class="modal-close" onclick="closeRepayModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="repayLoanForm" onsubmit="submitRepayment(event)">
                    <input type="hidden" id="repayLoanId">
                    <p style="margin-bottom: 15px; color: #64748b;">
                        Outstanding Principal: <strong id="repayOutstandingDisplay">₹0.00</strong>
                    </p>

                    <div class="form-group">
                        <label class="form-label">Repayment Amount <span class="required">*</span></label>
                        <input type="number" id="repayAmount" class="form-input" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="repayDate" class="form-input" required>
                    </div>

                    <div class="alert-box"
                        style="margin-top: 15px; font-size: 13px; color: #1e293b; background: #f1f5f9; padding: 10px; border-radius: 4px;">
                        Note: Full payment will automatically close the loan.
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeRepayModal()">Cancel</button>
                        <button type="submit" class="btn-save">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Report Modal -->
    <div id="addReportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Report</h3>
                <button class="modal-close" onclick="closeAddReportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addReportForm" onsubmit="submitAddReport(event)">
                    <div class="form-group">
                        <label class="form-label">Month <span class="required">*</span></label>
                        <input type="month" id="reportMonth" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" id="reportOpeningBalance" class="form-input" placeholder="0.00" step="0.01" value="0.00">
                        <small class="form-hint">Set to 0 if this is not the first report.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode</label>
                        <select id="reportPaymentMode" class="form-select">
                            <option value="HDFC Bank">HDFC Bank</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddReportModal()">Cancel</button>
                        <button type="submit" class="btn-save">Create Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Income Modal -->
    <div id="editIncomeModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Edit Income</h3>
                <button class="modal-close" onclick="closeEditIncomeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editIncomeForm" onsubmit="submitEditIncome(event)" enctype="multipart/form-data">
                    <input type="hidden" id="editIncomeId">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Date & Time <span class="required">*</span></label>
                            <input type="datetime-local" id="editIncomeDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="editIncomeAmount" class="form-input" placeholder="0.00" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="editIncomeCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="editIncomeDescription" class="form-input" placeholder="Enter description" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="editIncomePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="editIncomeAttachment" class="form-input">
                            <small class="form-hint" id="editIncomeAttachmentHint" style="color: #6366f1;"></small>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditIncomeModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Income</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div id="editExpenseModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Edit Expense</h3>
                <button class="modal-close" onclick="closeEditExpenseModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm" onsubmit="submitEditExpense(event)" enctype="multipart/form-data">
                    <input type="hidden" id="editExpenseId">

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Date & Time <span class="required">*</span></label>
                            <input type="datetime-local" id="editExpenseDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="editExpenseAmount" class="form-input" placeholder="0.00" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="editExpenseCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="editExpenseDescription" class="form-input" placeholder="Enter description" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="editExpensePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="editExpenseAttachment" class="form-input">
                            <small class="form-hint" id="editExpenseAttachmentHint" style="color: #6366f1;"></small>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditExpenseModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div id="loanHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Loan Payment History</h3>
                <button class="modal-close" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-container">
                    <table class="table" id="loanHistoryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Non-GST Invoice Modal -->
    <div id="nonGstInvoiceModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Non-GST Invoice Details</h3>
                <button class="modal-close" onclick="closeNonGstModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="nonGstInvoiceForm" onsubmit="generateNonGstInvoice(event)">
                    <div class="form-group">
                        <label class="form-label">Bill To Name <span class="required">*</span></label>
                        <input type="text" id="nonGstBillToName" class="form-input" placeholder="Enter customer name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="nonGstPhone" class="form-input" placeholder="Enter phone number" required oninput="checkExistingUser(this.value, 'non-gst')">
                        <div id="existingUserNonGst" style="margin-top: 10px;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="nonGstEmail" class="form-input" placeholder="Enter email (optional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" id="nonGstAddress" class="form-input" placeholder="Enter address (optional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Invoice Items <span class="required">*</span></label>
                        <div id="nonGstItemsContainer">
                            <div class="invoice-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
                                <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
                                <button type="button" class="btn-add-item" onclick="addNonGstItem()">+</button>
                            </div>
                        </div>
                        <button type="button" id="btnNonGstDone" class="btn-primary" style="margin-top: 10px; background-color: #0ea5e9; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;" onclick="clickNonGstDone()">Done</button>
                    </div>

                    <!-- Payment & Summary Section -->
                    <div id="nonGstPaymentSummarySection" style="display: none; border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 15px;">
                        <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 15px; font-weight: 600;">Payment & Summary</h4>
                        
                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Mode <span class="required">*</span></label>
                                <select id="nonGstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Date <span class="required">*</span></label>
                                <input type="date" id="nonGstPaymentDate" class="form-input">
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Amount Paid <span class="required">*</span></label>
                                <input type="number" id="nonGstAmountPaid" class="form-input" placeholder="Enter amount paid" step="0.01" min="0" oninput="updateNonGstSummary()">
                            </div>
                        </div>
                        
                        <!-- Summary Box -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Total Amount:</span>
                                <span id="summaryNonGstTotal" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Amount Paid:</span>
                                <span id="summaryNonGstPaid" style="font-weight: 700; color: #10b981;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 600;">Balance Due:</span>
                                <span id="summaryNonGstDue" style="font-weight: 700; color: #ef4444;">₹0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeNonGstModal()">Cancel</button>
                        <button type="submit" id="btnNonGstGenerate" class="btn-save" style="display: none;">Generate Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GST Invoice Modal -->
    <div id="gstInvoiceModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>GST Invoice Details</h3>
                <button class="modal-close" onclick="closeGstModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="gstInvoiceForm" onsubmit="generateGstInvoice(event)">
                    <div class="form-group">
                        <label class="form-label">Bill To Name <span class="required">*</span></label>
                        <input type="text" id="gstBillToName" class="form-input" placeholder="Enter customer name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="gstPhone" class="form-input" placeholder="Enter phone number" required oninput="checkExistingUser(this.value, 'gst')">
                        <div id="existingUserGst" style="margin-top: 10px;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">GST Number <span class="required">*</span></label>
                        <input type="text" id="gstModalNumber" class="form-input" placeholder="Enter GST number" required pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}">
                        <small style="color: #64748b; font-size: 12px;">Format: 22AAAAA0000A1Z5</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="gstEmail" class="form-input" placeholder="Enter email (optional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <input type="text" id="gstAddress" class="form-input" placeholder="Enter address (optional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Invoice Items <span class="required">*</span></label>
                        <div id="gstItemsContainer">
                            <div class="invoice-item-row-gst" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
                                <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstItemAmountChange()">
                                <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                                    <input type="checkbox" class="gst-desc-check"> Desc.%
                                </label>
                                <button type="button" class="btn-add-item" onclick="addGstItem()">+</button>
                            </div>
                        </div>
                        <button type="button" id="btnGstDone" class="btn-primary" style="margin-top: 10px; background-color: #0ea5e9; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;" onclick="clickGstDone()">Done</button>
                    </div>

                    <!-- Payment & Summary Section -->
                    <div id="gstPaymentSummarySection" style="display: none; border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 15px;">
                        <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 15px; font-weight: 600;">Payment & Summary</h4>
                        
                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Mode <span class="required">*</span></label>
                                <select id="gstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Date <span class="required">*</span></label>
                                <input type="date" id="gstPaymentDate" class="form-input">
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Amount Paid <span class="required">*</span></label>
                                <input type="number" id="gstAmountPaid" class="form-input" placeholder="Enter amount paid" step="0.01" min="0" oninput="updateGstSummary()">
                            </div>
                        </div>
                        
                        <!-- Summary Box -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Charges (Excl. Tax):</span>
                                <span id="summaryGstCharges" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">GST (18%):</span>
                                <span id="summaryGstTax" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Total Amount (Incl. Tax):</span>
                                <span id="summaryGstTotal" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Amount Paid:</span>
                                <span id="summaryGstPaid" style="font-weight: 700; color: #10b981;">₹0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 600;">Balance Due:</span>
                                <span id="summaryGstDue" style="font-weight: 700; color: #ef4444;">₹0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeGstModal()">Cancel</button>
                        <button type="submit" id="btnGstGenerate" class="btn-save" style="display: none;">Generate Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Voucher Modal -->
    <div id="voucherModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Payment Voucher Details</h3>
                <button class="modal-close" onclick="closeVoucherModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="voucherForm" onsubmit="generateVoucher(event)">
                    <div class="form-group">
                        <label class="form-label">To whom (Payee Name) <span class="required">*</span></label>
                        <input type="text" id="voucherPayee" class="form-input" placeholder="Enter name of the person/company" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount <span class="required">*</span></label>
                        <input type="number" id="voucherAmount" class="form-input" placeholder="0.00" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mode of Payment <span class="required">*</span></label>
                        <select id="voucherMode" class="form-select" required>
                            <option value="Cash" selected>Cash</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date <span class="required">*</span></label>
                        <input type="date" id="voucherDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Being (Description) <span class="required">*</span></label>
                        <textarea id="voucherDescription" class="form-input" placeholder="Purpose of payment" required style="height: 80px;"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeVoucherModal()">Cancel</button>
                        <button type="submit" class="btn-save">Generate Voucher</button>
                    </div>
                </form>
            </div>
        </div>
    <!-- Add/Edit Quotation Modal -->
    <div id="quotationModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3 id="quotationModalTitle">Create Corporate Quotation</h3>
                <button class="modal-close" onclick="closeQuotationModal()">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="quotationForm" onsubmit="saveQuotation(event)">
                    <input type="hidden" id="quotationId" value="">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Client Name <span class="required">*</span></label>
                            <input type="text" id="qClientName" class="form-input" required placeholder="e.g. John Doe">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Phone Number <span class="required">*</span></label>
                            <input type="text" id="qClientPhone" class="form-input" required placeholder="e.g. 9876543210">
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="qClientEmail" class="form-input" placeholder="e.g. client@example.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GST Number (Optional)</label>
                            <input type="text" id="qClientGst" class="form-input" placeholder="e.g. 29ABCDE1234F1Z5">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Billing Address</label>
                            <textarea id="qClientAddress" class="form-input" rows="2" placeholder="Enter billing address"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Quotation Date <span class="required">*</span></label>
                            <input type="date" id="qDate" class="form-input" required>
                        </div>
                    </div>

                    <h4 style="margin-top: 25px; margin-bottom: 10px; color: #fff;">Quotation Items</h4>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Qty</th>
                                    <th>Rate (₹)</th>
                                    <th>Amount (₹)</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="qItemsTableBody">
                                <!-- Dynamically added items -->
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px; margin-bottom: 25px;">
                        <button type="button" class="btn-secondary" onclick="addQuotationItemRow()">+ Add Item</button>
                    </div>

                    <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--border-light); padding-top: 15px;">
                        <div style="text-align: right; font-size: 16px;">
                            <p style="color: var(--text-muted);">Subtotal: <span id="qSubtotal" style="color: #fff; font-weight: 600;">₹0.00</span></p>
                            <p style="color: var(--text-muted); margin-top: 5px;">GST (18%): <span id="qGst" style="color: #fff; font-weight: 600;">₹0.00</span></p>
                            <h3 style="font-size: 22px; color: #fff; margin-top: 10px;">Total Amount: <span id="qTotal">₹0.00</span></h3>
                        </div>
                    </div>

                    <div class="modal-actions" style="margin-top: 30px;">
                        <button type="button" class="btn-cancel" onclick="closeQuotationModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save & Generate Quotation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="js/invoice_functions.js"></script>
    <script>
        function toggleInvoiceDropdown(event) {
            event.preventDefault();
            const dropdown = document.getElementById('invoiceDropdown');
            dropdown.classList.toggle('show');
        }

        function logout() {
            window.location.href = 'api/logout.php';
        }

        // Close Invoice Type Selection Dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('invoiceTypeSelectionDiv');
            const container = document.querySelector('.generate-invoice-container');
            if (dropdown && container && !container.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Add this to handle generic page transitions for dynamic content
        document.querySelectorAll('.dropdown-item, .nav-item:not(.has-dropdown)').forEach(item => {
            item.addEventListener('click', function(e) {
                const page = this.getAttribute('data-page');
                if (page === 'voucher') {
                    if (typeof loadVouchers === 'function') {
                        loadVouchers();
                    }
                }
                if (page === 'payslip') {
                    if (typeof loadPayslips === 'function') {
                        loadPayslips();
                    }
                }
            });
        });
    </script>
    <!-- Payslip Modal -->
    <div id="payslipModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Generate Employee Payslip</h3>
                <button class="modal-close" onclick="closePayslipModal()">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="payslipForm" onsubmit="generatePayslip(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Employee Info -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600;">Employee Details</legend>
                            <div class="form-group">
                                <label class="form-label">Employee Name <span class="required">*</span></label>
                                <input type="text" id="payEmpName" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Employee ID <span class="required">*</span></label>
                                <input type="text" id="payEmpNo" class="form-input" placeholder="e.g. 2902188" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Month/Year <span class="required">*</span></label>
                                <input type="month" id="payMonth" class="form-input" value="2026-03" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Designation</label>
                                <input type="text" id="payGrade" class="form-input" placeholder="e.g. Software Engineer">
                            </div>
                        </fieldset>

                        <!-- Bank & Leave -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600;">Bank & Leave</legend>
                            <div class="form-group">
                                <label class="form-label">Bank Name</label>
                                <input type="text" id="payBank" class="form-input" placeholder="e.g. HDFC Bank">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Account No</label>
                                <input type="text" id="payAcc" class="form-input" placeholder="XXXXXXXX4779">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Days Paid</label>
                                <input type="number" id="payDays" class="form-input" value="31">
                            </div>
                        </fieldset>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <!-- Earnings -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600; color: #10b981;">Earnings (₹)</legend>
                            <div class="form-group"><label class="form-label">Basic Salary</label><input type="number" id="payBasic" class="form-input" value="15000"></div>
                            <div class="form-group"><label class="form-label">HRA</label><input type="number" id="payHra" class="form-input" value="6000"></div>
                            <div class="form-group"><label class="form-label">Other Allowance</label><input type="number" id="payOther" class="form-input" value="5000"></div>
                        </fieldset>

                        <!-- Deductions -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600; color: #ef4444;">Deductions (₹)</legend>
                            <div class="form-group"><label class="form-label">Provident Fund</label><input type="number" id="payPf" class="form-input" value="1800"></div>
                            <div class="form-group"><label class="form-label">Health Insurance</label><input type="number" id="payHealth" class="form-input" value="200"></div>
                        </fieldset>
                    </div>

                    <div class="modal-actions" style="margin-top: 30px;">
                        <button type="button" class="btn-cancel" onclick="closePayslipModal()">Cancel</button>
                        <button type="submit" class="btn-save">Generate Payslip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function logout() {
            window.location.href = 'api/logout.php';
        }
    </script>
</body>

</html>