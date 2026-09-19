<?php
// Financial Year Helper Functions

if (!function_exists('getFinancialYearDates')) {
    function getFinancialYearDates() {
        $selected_fy = $_COOKIE['financial_year'] ?? '';
        if (preg_match('/^(\d{4})-(\d{4})$/', $selected_fy, $matches)) {
            $fy_start_year = intval($matches[1]);
            $fy_end_year = intval($matches[2]);
        } else {
            $currentMonth = intval(date('n'));
            $currentYear = intval(date('Y'));
            $fy_start_year = ($currentMonth >= 4) ? $currentYear : $currentYear - 1;
            $fy_end_year = $fy_start_year + 1;
        }
        
        $start_date = sprintf("%04d-04-01", $fy_start_year);
        $end_date = sprintf("%04d-03-31", $fy_end_year);
        
        return [
            'start_year' => $fy_start_year,
            'end_year' => $fy_end_year,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_datetime' => "$start_date 00:00:00",
            'end_datetime' => "$end_date 23:59:59",
            'fy_string' => "$fy_start_year-$fy_end_year"
        ];
    }
}

if (!function_exists('getFinancialYearYearFromDate')) {
    function getFinancialYearYearFromDate($dateStr) {
        if (empty($dateStr)) $dateStr = date('Y-m-d');
        $time = strtotime($dateStr);
        $year = intval(date('Y', $time));
        $month = intval(date('n', $time));
        // In Indian Financial Year (Apr 1 - Mar 31), months Jan, Feb, Mar (1,2,3) belong to the previous calendar year's FY!
        if ($month <= 3) {
            return strval($year - 1);
        }
        return strval($year);
    }
}

