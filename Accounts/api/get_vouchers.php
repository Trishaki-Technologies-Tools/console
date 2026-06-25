<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Detect actual vouchers columns
    $cols = [];
    $cr = $conn->query("SHOW COLUMNS FROM vouchers");
    while ($c = $cr->fetch_assoc()) $cols[] = $c['Field'];

    $refCol   = in_array('voucher_no', $cols)   ? 'voucher_no'   : (in_array('ref_no', $cols)     ? 'ref_no'     : 'id');
    $payeeCol = in_array('payee_name', $cols)   ? 'payee_name'   : (in_array('payee', $cols)       ? 'payee'      : "''");
    $modeCol  = in_array('payment_mode', $cols) ? 'payment_mode' : (in_array('mode', $cols)        ? 'mode'       : "''");
    $dateCol  = in_array('date', $cols)         ? 'date'         : (in_array('created_at', $cols)  ? 'created_at' : 'id');

    $sql = "SELECT id,
                {$refCol} AS ref_no,
                {$payeeCol} AS payee,
                amount,
                {$modeCol} AS mode,
                {$dateCol} AS date,
                description,
                created_at
            FROM vouchers
            ORDER BY {$dateCol} DESC, id DESC";

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $vouchers = [];
    while ($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
    echo json_encode($vouchers);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
