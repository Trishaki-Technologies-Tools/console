<?php
require_once __DIR__ . '/../api/config.php';

$phone = '7411082418';
$duplicatePhone = '7411082418-2';

// 1. Get current client ID for phone 7411082418
$stmt = $conn->prepare("SELECT id, name FROM clients WHERE phone = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    $clientId = $row['id'];
    echo "Found client with ID $clientId (Current Name: " . $row['name'] . ")\n";

    // Update current client name back to Shrijit Mense
    $updName = $conn->prepare("UPDATE clients SET name = ?, email = '' WHERE id = ?");
    $newName = 'Shrijit Mense';
    $updName->bind_param("si", $newName, $clientId);
    $updName->execute();
    echo "Updated client ID $clientId name to 'Shrijit Mense'.\n";

    // 2. Create new client for Sharvari Nagoji with phone 7411082418-2
    $ins = $conn->prepare("INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)");
    $sharvariName = 'Sharvari Nagoji';
    $sharvariEmail = '';
    $ins->bind_param("sss", $sharvariName, $duplicatePhone, $sharvariEmail);
    $ins->execute();
    $newClientId = $conn->insert_id;
    echo "Created new client 'Sharvari Nagoji' (ID $newClientId) with phone '$duplicatePhone'.\n";

    // 3. Update invoice TSK-2026-023 to link to new client ID
    $updInv = $conn->prepare("UPDATE invoices SET client_id = ? WHERE invoice_no = 'TSK-2026-023'");
    $updInv->bind_param("i", $newClientId);
    $updInv->execute();
    echo "Updated invoice TSK-2026-023 client_id to $newClientId.\n";

    // 4. Update receipt RECP-2026-024 to link to new client ID
    $updRec = $conn->prepare("UPDATE receipts SET client_id = ? WHERE receipt_no = 'RECP-2026-024'");
    $updRec->bind_param("i", $newClientId);
    $updRec->execute();
    echo "Updated receipt RECP-2026-024 client_id to $newClientId.\n";
} else {
    echo "Client with phone $phone not found.\n";
}
?>
