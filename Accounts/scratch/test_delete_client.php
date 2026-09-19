<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';

$conn->query("INSERT INTO clients (name, phone) VALUES ('Delete Test', '9999911111')");
$id = $conn->insert_id;
echo "Created client ID: $id\n";

$_GET['id'] = $id;
ob_start();
require 'c:/xampp/htdocs/console/Accounts/api/delete_client.php';
$out = ob_get_clean();
echo "delete_client output: $out\n";
$res = json_decode($out, true);
assert($res && $res['success'] === true, "Client should be deleted successfully");
echo "=== TEST PASSED! ===\n";
