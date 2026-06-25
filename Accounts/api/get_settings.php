<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $result = $conn->query("SELECT `key`, `value` FROM settings");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['key']] = $row['value'];
    }
    
    // Add default values for common keys if they don't exist
    $defaults = [
        'company_name' => 'TRISHAKI TECHNOLOGIES PRIVATE LIMITED',
        'company_address' => 'F1, First Floor, Star Tower, RPD Circle, Opposite Canara Bank, Tilakwadi, Belagavi, Karnataka - 590006',
        'company_phone' => '+91 9980681304',
        'company_email' => 'info@trishaki.com',
        'company_gst' => '29ABCDE1234F1Z5',
        'currency' => 'INR'
    ];
    
    foreach ($defaults as $key => $val) {
        if (!isset($settings[$key])) {
            $settings[$key] = $val;
        }
    }
    
    echo json_encode($settings);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
