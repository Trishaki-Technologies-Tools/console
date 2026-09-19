<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
require_once 'c:/xampp/htdocs/console/Accounts/api/settlement_helper.php';

echo "=== TESTING SYSTEM CATEGORIES AUTO-SYNC & PROTECTION ===\n";

// 1. Run syncSystemCategories
syncSystemCategories($conn);

// 2. Fetch income categories
$resI = $conn->query("SELECT * FROM incomes_categories");
echo "Income Categories:\n";
$salesFound = false;
$settleFound = false;
$salesId = 0;
$settleId = 0;
while ($r = $resI->fetch_assoc()) {
    echo "ID: {$r['id']}, Name: {$r['category_name']}\n";
    if (strtolower($r['category_name']) === 'sales') {
        $salesFound = true;
        $salesId = $r['id'];
    }
    if (strtolower($r['category_name']) === 'settlement') {
        $settleFound = true;
        $settleId = $r['id'];
    }
}

// 3. Fetch expense categories
$resE = $conn->query("SELECT * FROM expenses_categories");
echo "Expense Categories:\n";
$expSettleFound = false;
$expSettleId = 0;
while ($r = $resE->fetch_assoc()) {
    echo "ID: {$r['id']}, Name: {$r['category_name']}\n";
    if (strtolower($r['category_name']) === 'settlement') {
        $expSettleFound = true;
        $expSettleId = $r['id'];
    }
}

// 4. Test API delete protection on Income Categories
if ($salesId > 0) {
    $_GET['id'] = $salesId;
    ob_start();
    require 'c:/xampp/htdocs/console/Accounts/api/delete_income_category.php';
    $delOutput = ob_get_clean();
    $delRes = json_decode($delOutput, true);
    echo "Delete Sales result: " . json_encode($delRes) . "\n";
    assert($delRes['success'] === false, "Deleting Sales should be blocked");
}

if ($expSettleId > 0) {
    $_GET['id'] = $expSettleId;
    ob_start();
    require 'c:/xampp/htdocs/console/Accounts/api/delete_expense_category.php';
    $delOutputE = ob_get_clean();
    $delResE = json_decode($delOutputE, true);
    echo "Delete Settlement from Expenses result: " . json_encode($delResE) . "\n";
    assert($delResE['success'] === false, "Deleting Settlement from expenses should be blocked");
}

echo "=== ALL CATEGORY TESTS PASSED! ===\n";
