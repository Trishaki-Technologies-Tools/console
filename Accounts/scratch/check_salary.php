<?php
require 'api/config.php';
$res = $conn->query('DESCRIBE employees');
while ($row = $res->fetch_assoc()) print_r($row);
