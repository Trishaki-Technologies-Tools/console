<?php
require_once __DIR__ . '/../api/config.php';

function describeTable($conn, $tableName) {
    echo "--- Table: $tableName ---\n";
    try {
        $result = $conn->query("DESCRIBE $tableName");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                printf("%-25s %-15s %-10s %-5s %-10s %s\n", 
                    $row['Field'], $row['Type'], $row['Null'], $row['Key'], $row['Default'], $row['Extra']);
            }
        } else {
            echo "Failed to describe table (result empty).\n";
        }
    } catch (Exception $e) {
        echo "Error describing table: " . $e->getMessage() . "\n";
    }
}

describeTable($conn, 'invoices');
describeTable($conn, 'receipts');
describeTable($conn, 'clients');
describeTable($conn, 'customers');
?>
