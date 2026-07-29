<?php
$content = file_get_contents('index.php');

// Fix spacing first!
$content = str_replace(
    '<link rel="stylesheet" href="css/style.css?v=2">',
    '<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        /* Fallback Layout Spacing Fixes */
        .stats-row-small { display: flex !important; flex-wrap: wrap; gap: 20px !important; margin-bottom: 24px !important; }
        .stat-card-small { flex: 1 1 220px; min-width: 220px; }
        .dashboard-section { margin-bottom: 35px !important; }
        .records-section { margin-top: 24px !important; margin-bottom: 24px !important; }
    </style>',
    $content
);

// We know the Rupee symbol and some emojis got completely garbled into ?0.00 and others.
// We will use regex to find those and replace them.

// Fix Rupee symbol: Currently it appears as `?` or `` right before numbers, e.g. `?0.00` or `?10.00`.
// Let's replace any `?` or `` followed by a digit with `₹`.
// Also, in some places it's `Amount (?)` or `Earnings (?)`.
$content = preg_replace('/(\?|)(\d)/', '₹$2', $content);
$content = str_replace('Amount (?)', 'Amount (₹)', $content);
$content = str_replace('Earnings (?)', 'Earnings (₹)', $content);
$content = str_replace('Deductions (?)', 'Deductions (₹)', $content);
$content = str_replace('Payment Received (?)', 'Payment Received (₹)', $content);
$content = str_replace('Discount Amount (?)', 'Discount Amount (₹)', $content);
$content = str_replace('Rate (?)', 'Rate (₹)', $content);

// Fix Emojis
$content = preg_replace('/<div class="stat-icon-small icon-blue">.*?<\/div>/s', '<div class="stat-icon-small icon-blue">💰</div>', $content, 1); // Total Incomes Value
$content = preg_replace('/<div class="stat-icon-small icon-purple">.*?<\/div>/s', '<div class="stat-icon-small icon-purple">📈</div>', $content, 1); // Incomes Count

// There might be others, let's fix "Manage Categories" button
$content = preg_replace('/>.*?Manage Categories<\/button>/', '>📊 Manage Categories</button>', $content);

file_put_contents('index.php', $content);
echo "Fixed layout and corrupted symbols!";
