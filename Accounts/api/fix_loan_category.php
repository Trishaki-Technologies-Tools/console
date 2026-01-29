<?php
require_once 'config.php';

// 1. Add 'Loan' Category
$cat = 'Loan';
$check = $conn->query("SELECT id FROM income_categories WHERE category_name = '$cat'");
if ($check->num_rows == 0) {
    echo "Adding '$cat' category...\n";
    $conn->query("INSERT INTO income_categories (category_name) VALUES ('$cat')");
} else {
    echo "'$cat' category already exists.\n";
}

// 2. Update existing Income records where description matches "Loan%"
echo "Updating existing loan incomes...\n";
$update = $conn->query("UPDATE incomes SET category = 'Loan' WHERE description LIKE 'Loan%' AND category != 'Loan'");
echo "Updated " . $conn->affected_rows . " records.\n";

$conn->close();
?>
