<?php
require_once __DIR__ . '/../api/config.php';

$updates = [
    ['old' => 'QT-2026-001', 'new' => 'QT-2026-101'],
    ['old' => 'QT-2026-002', 'new' => 'QT-2026-102'],
];

foreach ($updates as $u) {
    $stmt = $conn->prepare("UPDATE quotations SET quotation_no = ? WHERE quotation_no = ?");
    $stmt->bind_param("ss", $u['new'], $u['old']);
    $stmt->execute();
    echo $stmt->affected_rows > 0
        ? "✅ Updated {$u['old']} → {$u['new']}\n"
        : "⚠️  Not found: {$u['old']}\n";
    $stmt->close();
}

echo "Done!\n";
