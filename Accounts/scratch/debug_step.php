<?php
require_once 'c:/xampp/htdocs/console/Accounts/api/config.php';
$res = $conn->query("DESCRIBE merchant_settlements");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
