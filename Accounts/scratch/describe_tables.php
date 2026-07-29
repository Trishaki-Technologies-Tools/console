<?php
require_once dirname(__DIR__) . '/api/config.php';

function describeTable($conn, $table) {
    echo "--- Table: $table ---\n";
    $res = $conn->query("DESCRIBE `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "Field: {$row['Field']} | Type: {$row['Type']} | Null: {$row['Null']} | Default: {$row['Default']}\n";
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}

describeTable($conn, 'incomes');
describeTable($conn, 'expenses');
describeTable($conn, 'transactions');
