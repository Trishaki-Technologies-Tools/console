<?php
header('Content-Type: application/json');
require_once 'config.php';

try {
    // Detect actual salary_logs columns
    $sCols = [];
    $r = $conn->query("SHOW COLUMNS FROM salary_logs");
    while ($c = $r->fetch_assoc()) $sCols[] = $c['Field'];

    // Detect actual employees columns
    $eCols = [];
    $r2 = $conn->query("SHOW COLUMNS FROM employees");
    while ($c = $r2->fetch_assoc()) $eCols[] = $c['Field'];

    $hasMonthYear = in_array('month_year', $sCols);
    $hasMonthCol  = in_array('month', $sCols);
    $hasModeId    = in_array('payment_mode_id', $sCols);
    $hasModeText  = in_array('payment_mode', $sCols);
    $hasStatus    = in_array('status', $sCols);
    $hasEmpRole   = in_array('role', $eCols);

    $monthSelect  = $hasMonthYear ? "s.month_year" : ($hasMonthCol ? "s.month" : "''");
    $modeSelect   = $hasModeId   ? "COALESCE(pm.mode_name, 'Bank Transfer')" : ($hasModeText ? "s.payment_mode" : "'Bank Transfer'");
    $statusSelect = $hasStatus   ? "s.status" : "'Paid'";
    $roleSelect   = $hasEmpRole  ? "COALESCE(e.role, '')" : "''";
    $modeJoin     = $hasModeId   ? "LEFT JOIN payment_modes pm ON s.payment_mode_id = pm.id" : "";

    $sql = "SELECT 
                s.id,
                s.amount,
                s.payment_date,
                {$monthSelect} AS month,
                {$modeSelect} AS payment_mode,
                {$statusSelect} AS status,
                e.name AS employee_name,
                {$roleSelect} AS role
            FROM salary_logs s
            JOIN employees e ON s.employee_id = e.id
            {$modeJoin}
            ORDER BY s.payment_date DESC";

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $logs = [];
    while ($row = $result->fetch_assoc()) $logs[] = $row;

    echo json_encode($logs);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
