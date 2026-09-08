<?php
$page_title = "Audit Logs";
$current_page = "audit-logs";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="audit-logs-page">
                    <div class="section-header">
                        <div>
                            <h2>Security Audit Trail</h2>
                            <p class="company-subtitle">Centralized database audit logs and operational history</p>
                        </div>
                        <input type="text" id="audit-search" class="form-input" placeholder="Search logs..."
                            onkeyup="filterAuditLogs()" style="max-width: 250px;">
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="audit-logs-container">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading audit logs...</p>
                        </div>
                    </div>
                </div>

                <!-- Settings Content -->

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>