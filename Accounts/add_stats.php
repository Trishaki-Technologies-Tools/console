<?php
$content = file_get_contents('index.php');

$clients_target = '<div class="records-section">
                        <div class="table-responsive" id="clients-list-container">';

$clients_stats = '<div class="stats-row-small" style="margin-top: 15px; margin-bottom: 25px;">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">&#128100;</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="client-total-count">0</div>
                                <div class="stat-label-small">Total Clients</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">&#127891;</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="student-total-count">0</div>
                                <div class="stat-label-small">Total Students</div>
                            </div>
                        </div>
                    </div>';
// Add to clients
$content = str_replace($clients_target, $clients_stats . "\n" . $clients_target, $content);

$quotations_target = '<div class="records-section">
                        <div class="table-responsive" id="quotations-list-container">';
$quotations_stats = '<div class="stats-row-small" style="margin-top: 15px; margin-bottom: 25px;">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">&#10004;</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="quotation-accepted-count">0</div>
                                <div class="stat-label-small">Accepted</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-red">&#10006;</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="quotation-rejected-count">0</div>
                                <div class="stat-label-small">Rejected</div>
                            </div>
                        </div>
                    </div>';
// Add to quotations
$content = str_replace($quotations_target, $quotations_stats . "\n" . $quotations_target, $content);

file_put_contents('index.php', $content);

// Update js/app.js
$app_js = file_get_contents('js/app.js');

// 1. Client counts
$client_js_old = "window.allClients = data;
            
            // Populate datalist for invoices";

$client_js_new = "window.allClients = data;
            
            // Update Client Stats
            const clientCount = data.filter(c => c.client_type === 'Client').length;
            const studentCount = data.filter(c => c.client_type === 'Student').length;
            if(document.getElementById('client-total-count')) document.getElementById('client-total-count').textContent = clientCount;
            if(document.getElementById('student-total-count')) document.getElementById('student-total-count').textContent = studentCount;

            // Populate datalist for invoices";
$app_js = str_replace($client_js_old, $client_js_new, $app_js);

// 2. Quotation counts
$quote_js_old = "const tbody = document.getElementById('quotations-list-container');";

$quote_js_new = "const tbody = document.getElementById('quotations-list-container');
            
            // Update Quotation Stats
            const acceptedCount = data.filter(q => q.status === 'Accepted').length;
            const rejectedCount = data.filter(q => q.status === 'Rejected').length;
            if(document.getElementById('quotation-accepted-count')) document.getElementById('quotation-accepted-count').textContent = acceptedCount;
            if(document.getElementById('quotation-rejected-count')) document.getElementById('quotation-rejected-count').textContent = rejectedCount;
";
$app_js = str_replace($quote_js_old, $quote_js_new, $app_js);

file_put_contents('js/app.js', $app_js);

echo "Added UI stats for clients and quotations.";
