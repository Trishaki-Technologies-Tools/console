<?php
require_once 'api/config.php';

try {
    $conn->query("CREATE TABLE IF NOT EXISTS colleges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE
    )");
    echo "Created colleges table.\n";
} catch (Exception $e) {
    echo "Error colleges table: " . $e->getMessage() . "\n";
}

try {
    $conn->query("CREATE TABLE IF NOT EXISTS departments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE
    )");
    echo "Created departments table.\n";
} catch (Exception $e) {
    echo "Error departments table: " . $e->getMessage() . "\n";
}
