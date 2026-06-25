<?php
ob_start();
header('Content-Type: application/json');
require_once 'config.php';
require_once 'invoice_utils.php';

function sendResponse($success, $data = []) {
    ob_clean();
    $response = array_merge(['success' => $success], $data);
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['csvFile'])) {
    sendResponse(false, ['error' => 'No file uploaded']);
}

$file = $_FILES['csvFile']['tmp_name'];
$handle = fopen($file, 'r');

if (!$handle) {
    sendResponse(false, ['error' => 'Could not open file']);
}

// Skip header row
$headers = fgetcsv($handle);

$count = 0;
try {
    $conn->begin_transaction();

    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0]) || count($row) < 2) continue; // Skip empty rows

        // Map CSV columns: BillToName, Phone, Email, Description, PaymentMode, Date, CourseFee, PaidNow, Type
        $name = trim($row[0]);
        $phone = trim($row[1]);
        $email = trim($row[2]);
        $desc = trim($row[3]);
        $mode = trim($row[4]);
        $rawDate = (!empty($row[5])) ? trim($row[5]) : "";
        
        // Normalize Date
        $date = "";
        if (!empty($rawDate)) {
            $normalizedRaw = str_replace(['.', ' '], '-', $rawDate);
            if (strpos($normalizedRaw, '/') !== false) {
                $parts = explode('/', $normalizedRaw);
                if (count($parts) === 3) {
                    if (strlen($parts[2]) === 2) $parts[2] = '20' . $parts[2];
                    $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                }
            } else if (strpos($normalizedRaw, '-') !== false) {
                $parts = explode('-', $normalizedRaw);
                if (count($parts) === 3) {
                    if (strlen($parts[0]) === 4) { $date = $normalizedRaw; } 
                    else {
                        if (strlen($parts[2]) === 2) $parts[2] = '20' . $parts[2];
                        $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                    }
                }
            } else { $date = $normalizedRaw; }
        }
        
        $timestamp = (!empty($date)) ? strtotime($date) : false;
        if ($timestamp) {
            $date = date('Y-m-d', $timestamp);
        } else {
            $date = date('Y-m-d');
        }

        $totalCharged = (isset($row[6]) && floatval($row[6]) > 0) ? floatval($row[6]) : 5000.00;
        $paidNow = (isset($row[7])) ? floatval($row[7]) : 0;
        $type = !empty($row[8]) ? strtolower(trim($row[8])) : 'non-gst';

        // 1. Client
        $stmt = $conn->prepare("SELECT id FROM clients WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $clientId = $res->fetch_assoc()['id'];
            $uStmt = $conn->prepare("UPDATE clients SET name = ?, email = ? WHERE id = ?");
            $uStmt->bind_param("ssi", $name, $email, $clientId);
            $uStmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO clients (name, phone, email) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $phone, $email);
            $stmt->execute();
            $clientId = $conn->insert_id;
        }

        // 2. Existing Invoices for /P logic
        $stmt = $conn->prepare("SELECT invoice_no FROM invoices WHERE client_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $continueFrom = ($res2 = $stmt->get_result()->fetch_assoc()) ? $res2['invoice_no'] : null;
        
        // 3. Accumulate Previous Payments
        $actualPaidSoFar = 0;
        $stmt = $conn->prepare("SELECT items FROM invoices WHERE client_id = ?");
        $stmt->bind_param("i", $clientId);
        $stmt->execute();
        $res3 = $stmt->get_result();
        while($invRow = $res3->fetch_assoc()) {
            $itemData = json_decode($invRow['items'], true);
            if (!empty($itemData)) {
                foreach($itemData as $it) {
                    $actualPaidSoFar += floatval($it['paidAmt'] ?? $it['amount'] ?? 0);
                }
            }
        }
        $cumulativeTotalForThisRow = $actualPaidSoFar + $paidNow;

        // 4. Prepare JSON
        $itemsArr = [[
            'description' => $desc,
            'paymentMode' => $mode,
            'date' => $date,
            'amount' => $totalCharged,
            'totalInclTax' => $totalCharged,
            'paidAmt' => $paidNow,
            'tax' => 0, 
            'charges' => $totalCharged 
        ]];
        $itemsJson = json_encode($itemsArr);

        // 5. Generate No and Insert
        $invoiceNo = generateInvoiceNumber($conn, $clientId, $type, $continueFrom, $itemsJson);
        
        $status = 'unpaid';
        if ($cumulativeTotalForThisRow >= $totalCharged - 0.01) {
            $status = 'paid';
        } elseif ($cumulativeTotalForThisRow > 0) {
            $status = 'partially_paid';
        }

        $stmt = $conn->prepare("INSERT INTO invoices (invoice_no, client_id, type, items, original_total_payable, cumulative_total_paid, invoice_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissddss", $invoiceNo, $clientId, $type, $itemsJson, $totalCharged, $cumulativeTotalForThisRow, $date, $status);
        $stmt->execute();
        
        $count++;
    }

    $conn->commit();
    sendResponse(true, ['count' => $count]);
} catch (Exception $e) {
    if ($conn) $conn->rollback();
    sendResponse(false, ['error' => $e->getMessage()]);
}
fclose($handle);
?>
