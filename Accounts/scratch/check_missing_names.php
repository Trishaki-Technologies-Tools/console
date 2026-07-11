<?php
require_once __DIR__ . '/../api/config.php';

// Load names from CSV
$csvFile = __DIR__ . '/../api/interns_payments_march.csv';
$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle);

$csvNames = [];
while (($row = fgetcsv($handle)) !== false) {
    if (!empty($row[0])) {
        $csvNames[] = trim($row[0]);
    }
}
fclose($handle);

echo "Total names in CSV: " . count($csvNames) . "\n";

// Fetch all names in DB
$dbNames = [];
$res = $conn->query("SELECT name FROM clients");
while ($r = $res->fetch_assoc()) {
    $dbNames[] = trim($r['name']);
}

echo "Total names in DB: " . count($dbNames) . "\n";

// Check if any CSV name is missing in DB
$missing = [];
foreach ($csvNames as $name) {
    if (!in_array($name, $dbNames)) {
        $missing[] = $name;
    }
}

if (count($missing) > 0) {
    echo "Missing names in DB:\n";
    print_r($missing);
} else {
    echo "All CSV names are in the DB!\n";
}
?>
