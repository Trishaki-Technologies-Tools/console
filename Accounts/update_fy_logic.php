<?php
$index = file_get_contents('index.php');

$search = <<<EOD
                    <select id="financialYear" class="form-input" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; background: #f8fafc; font-weight: 600; color: #1e293b; cursor: pointer; outline: none; min-width: 130px;" onchange="document.cookie = 'financial_year=' + this.value + '; path=/'; window.location.reload();">
                        <?php
                        \$startYear = 2025;
                        \$currentYear = date('Y');
                        \$currentMonth = date('n');
                        
                        // Financial year starts April 1st
                        \$endYear = (\$currentMonth >= 4) ? \$currentYear : \$currentYear - 1;
                        if (\$endYear < \$startYear) \$endYear = \$startYear;

                        \$currentFy = \$endYear . '-' . (\$endYear + 1);
                        \$selectedFy = isset(\$_COOKIE['financial_year']) ? \$_COOKIE['financial_year'] : \$currentFy;
EOD;

$replace = <<<EOD
                    <select id="financialYear" class="form-input" style="padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; background: #f8fafc; font-weight: 600; color: #1e293b; cursor: pointer; outline: none; min-width: 130px;" onchange="changeFinancialYear(this.value)">
                        <?php
                        \$startYear = 2025;
                        \$currentYear = date('Y');
                        \$currentMonth = date('n');
                        
                        // Financial year starts April 1st
                        \$endYear = (\$currentMonth >= 4) ? \$currentYear : \$currentYear - 1;
                        if (\$endYear < \$startYear) \$endYear = \$startYear;

                        \$currentFy = \$endYear . '-' . (\$endYear + 1);
                        // Force reset to current FY on every page load/refresh
                        setcookie('financial_year', \$currentFy, time() + 86400, '/');
                        \$_COOKIE['financial_year'] = \$currentFy;
                        \$selectedFy = \$currentFy;
EOD;

$index = str_replace($search, $replace, $index);

file_put_contents('index.php', $index);

$app = file_get_contents('js/app.js');
$app .= "\n\n// Change Financial Year Dynamically\n";
$app .= "window.changeFinancialYear = function(value) {\n";
$app .= "    document.cookie = 'financial_year=' + value + '; path=/';\n";
$app .= "    if(typeof loadDashboardStats === 'function') loadDashboardStats();\n";
$app .= "    if(typeof loadInvoices === 'function') loadInvoices();\n";
$app .= "    if(typeof loadTransactions === 'function') loadTransactions();\n";
$app .= "    if(typeof loadClients === 'function') loadClients();\n";
$app .= "    if(typeof loadSalaryLogs === 'function') loadSalaryLogs();\n";
$app .= "    if(typeof loadLoans === 'function') loadLoans();\n";
$app .= "    if(typeof fetchQuotations === 'function') fetchQuotations();\n";
$app .= "    if(typeof loadReports === 'function') loadReports();\n";
$app .= "    if(typeof showAlertPopup === 'function') showAlertPopup('Success', 'Financial Year changed to ' + value, 'success');\n";
$app .= "};\n";

file_put_contents('js/app.js', $app);

echo "Updated financial year logic.";
