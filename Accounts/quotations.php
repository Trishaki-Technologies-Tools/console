<?php
$page_title = "Quotations";
$current_page = "quotations";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="quotations-page">
                    <div class="section-header">
                        <div>
                            <h2>Corporate Quotations</h2>
                            <p class="company-subtitle">Draft and issue quotations to prospects</p>
                        </div>
                        <button class="btn-primary" onclick="openQuotationModal()">+ Create Quotation</button>
                    </div>

                    <div class="stats-row-small" style="margin-top: 15px; margin-bottom: 25px;">
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
                    </div>
<div class="records-section">
                        <div class="table-responsive" id="quotations-list-container">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading quotations...</p>
                        </div>
                    </div>
                </div>

                <!-- Audit Logs Content -->

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>