<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    $fy = getFinancialYearDates();
    $sql = "SELECT v.id,
                   v.ref_no,
                   v.payee,
                   v.amount,
                   COALESCE(m.mode_name, 'Cash') AS mode,
                   v.date,
                   v.description,
                   v.created_at
            FROM vouchers v
            LEFT JOIN payment_modes m ON v.payment_mode_id = m.id
            WHERE v.date >= '{$fy['start_date']}' AND v.date <= '{$fy['end_date']}'
            ORDER BY v.date DESC, v.id DESC";

    $result = $conn->query($sql);
    if (!$result) {
        $sql = "SELECT id, ref_no, payee, amount, 'Cash' as mode, date, description, created_at FROM vouchers WHERE date >= '{$fy['start_date']}' AND date <= '{$fy['end_date']}' ORDER BY date DESC, id DESC";
        $result = $conn->query($sql);
    }

    $vouchers = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vouchers[] = $row;
        }
    }
    echo json_encode($vouchers);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>
