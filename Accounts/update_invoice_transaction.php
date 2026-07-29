<?php
$content = file_get_contents('api/save_invoice.php');

$search = <<<EOD
    if ($isEditMode) {
        log_action($conn, 'EDIT', 'invoices', $existingInvoiceId, "Edited invoice: \$invoiceNo for \$billToName");
    } else {
        \$newInvoiceId = \$conn->insert_id;
        log_action($conn, 'ADD', 'invoices', \$newInvoiceId, "Generated invoice: \$invoiceNo for \$billToName");
    }
    
    // Commit transaction
    \$conn->commit();
EOD;

$replace = <<<EOD
    if (\$isEditMode) {
        log_action(\$conn, 'EDIT', 'invoices', \$existingInvoiceId, "Edited invoice: \$invoiceNo for \$billToName");
    } else {
        \$newInvoiceId = \$conn->insert_id;
        log_action(\$conn, 'ADD', 'invoices', \$newInvoiceId, "Generated invoice: \$invoiceNo for \$billToName");
    }
    
    // Sync with transactions ledger
    \$targetInvId = \$isEditMode ? \$existingInvoiceId : \$newInvoiceId;
    
    // Clear old transactions for this invoice instance
    \$delStmt = \$conn->prepare("DELETE FROM transactions WHERE reference_table = 'invoices' AND reference_id = ?");
    \$delStmt->bind_param("i", \$targetInvId);
    \$delStmt->execute();
    
    // Insert new transaction if there is a payment
    if (\$currentPaid > 0) {
        \$tStmt = \$conn->prepare("INSERT INTO transactions (type, amount, date, reference_id, reference_table, description) VALUES ('income', ?, ?, ?, 'invoices', ?)");
        \$desc = "Invoice Payment: " . \$invoiceNo . " (" . \$billToName . ")";
        \$tStmt->bind_param("dsis", \$currentPaid, \$invoiceDate, \$targetInvId, \$desc);
        \$tStmt->execute();
    }
    
    // Commit transaction
    \$conn->commit();
EOD;

$content = str_replace($search, $replace, $content);

file_put_contents('api/save_invoice.php', $content);
echo "Updated save_invoice.php to sync with transactions.";
