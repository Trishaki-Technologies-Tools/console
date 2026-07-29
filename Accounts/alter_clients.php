<?php
require_once 'api/config.php';

try {
    $conn->query("ALTER TABLE clients ADD COLUMN client_type VARCHAR(50) DEFAULT 'Client'");
    echo "Added client_type column.\n";
} catch (Exception $e) {
    echo "Error adding client_type: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE clients ADD COLUMN college_name VARCHAR(150) NULL");
    echo "Added college_name column.\n";
} catch (Exception $e) {
    echo "Error adding college_name: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE clients ADD COLUMN department VARCHAR(100) NULL");
    echo "Added department column.\n";
} catch (Exception $e) {
    echo "Error adding department: " . $e->getMessage() . "\n";
}
