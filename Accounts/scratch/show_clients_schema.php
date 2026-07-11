<?php
require_once __DIR__ . '/../api/config.php';
$res = $conn->query("SHOW CREATE TABLE clients");
$row = $res->fetch_assoc();
echo $row['Create Table'] . "\n";
?>
