<?php
// 1. Update index.php
$content = file_get_contents('index.php');

$old_payment_input = '<input type="number" id="newPaymentModeBalanceInput" class="category-input"
                            placeholder="Opening Balance (e.g. 10000.00)" step="0.01" style="width: 100%;">';

$content = str_replace($old_payment_input, '', $content);
file_put_contents('index.php', $content);

// 2. Update js/app.js
$app_js = file_get_contents('js/app.js');

// Look for addPaymentMode function
$old_add_payment = "function addPaymentMode() {
    const nameInput = document.getElementById('newPaymentModeInput');
    const balanceInput = document.getElementById('newPaymentModeBalanceInput');
    const name = nameInput.value.trim();
    const balance = balanceInput ? parseFloat(balanceInput.value) : 0;

    if (!name) {
        alert('Please enter a payment method name');
        return;
    }";

$new_add_payment = "function addPaymentMode() {
    const nameInput = document.getElementById('newPaymentModeInput');
    const name = nameInput.value.trim();
    const balance = 0; // Default to 0 since we removed the input

    if (!name) {
        alert('Please enter a payment method name');
        return;
    }";

$app_js = str_replace($old_add_payment, $new_add_payment, $app_js);

// Also look for another variation if that didn't match
$old_add_payment2 = "function addPaymentMode() {
    const name = document.getElementById('newPaymentModeInput').value;
    const balance = document.getElementById('newPaymentModeBalanceInput').value;";

$new_add_payment2 = "function addPaymentMode() {
    const name = document.getElementById('newPaymentModeInput').value;
    const balance = 0;";
    
$app_js = str_replace($old_add_payment2, $new_add_payment2, $app_js);

file_put_contents('js/app.js', $app_js);

echo "Removed Opening Balance from Manage Payments UI.";
