<?php
header('Content-Type: application/json');
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    
    foreach ($data as $key => $value) {
        $keyStr = strval($key);
        $valStr = strval($value);
        $stmt->bind_param("sss", $keyStr, $valStr, $valStr);
        $stmt->execute();
        
        // If updating enable_2fa key, sync it with 2fa_config.php file
        if ($keyStr === 'enable_2fa') {
            $isEnabled = ($valStr === '1' || $valStr === 'true') ? 'true' : 'false';
            $configFile = __DIR__ . '/../../2fa_config.php';
            $configContent = "<?php\n// Two-Factor Authentication (2FA) Global Configuration\n// Set to false for testing/development to bypass 2FA checks.\n// Set to true for production to enforce 2FA verification.\ndefine('ENABLE_2FA', $isEnabled);\n";
            file_put_contents($configFile, $configContent);
        }
    }
    
    $stmt->close();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
