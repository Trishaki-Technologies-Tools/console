<?php
$page_title = "Clients";
$current_page = "clients";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="clients-page">
                    <div class="section-header">
                        <div>
                            <h2>Clients CRM & Directory</h2>
                            <p class="company-subtitle">View active clients, students, invoice history and records</p>
                        </div>
                        <div class="filter-row" style="gap: 12px; align-items: center;">
                            <!-- Tabs for switching between Students and Clients -->
                            <div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 8px; gap: 4px;">
                                <button id="client-tab-students" class="ledger-tab-btn active" onclick="switchClientTab('Student')">&#127891; Students</button>
                                <button id="client-tab-clients" class="ledger-tab-btn" onclick="switchClientTab('Client')">&#128100; Clients</button>
                                <button id="client-tab-all" class="ledger-tab-btn" onclick="switchClientTab('all')">All Directory</button>
                            </div>
                            <button class="btn-primary" onclick="openAddClientModal()">+ Add Client / Student</button>
                        </div>
                    </div>

                    <div class="stats-row-small" style="margin-top: 15px; margin-bottom: 25px;">
                        <div class="stat-card-small" onclick="switchClientTab('Student')" style="cursor: pointer;" title="View Students Directory">
                            <div class="stat-icon-small icon-blue">&#127891;</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="student-total-count">0</div>
                                <div class="stat-label-small">Total Students</div>
                            </div>
                        </div>
                        <div class="stat-card-small" onclick="switchClientTab('Client')" style="cursor: pointer;" title="View Clients Directory">
                            <div class="stat-icon-small icon-purple">&#128100;</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="client-total-count">0</div>
                                <div class="stat-label-small">Total Clients</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="clients-list-container">
                            <!-- Populated dynamically by clients load -->
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading directory list...</p>
                        </div>
                    </div>
                </div>

                <!-- Quotations Content -->

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>