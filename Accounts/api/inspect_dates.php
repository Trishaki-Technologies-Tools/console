<?php
require_once 'config.php';

echo "Incomes Dates:\n";
$result = $conn->query("SELECT date FROM incomes ORDER BY date");
while($row = $result->fetch_assoc()) {
    echo $row['date'] . "\n";
}

echo "\nExpenses Dates:\n";
$result = $conn->query("SELECT date FROM expenses ORDER BY date");
while($row = $result->fetch_assoc()) {
    echo $row['date'] . "\n";
}
$conn->close();
?>
