<?php
$page_title = "Loans";
$current_page = "loans";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="loans-page">
                    <div class="section-header">
                        <div>
                            <h2>Liability & Loans Tracker</h2>
                            <p class="company-subtitle">Monitor liabilities, interest and principal payments</p>
                        </div>
                        <button class="btn-primary" onclick="showAddLoanModal()">+ Add Loan</button>
                    </div>

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">💰</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-loan-taken">₹0</div>
                                <div class="stat-label-small">Total Loan Taken</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-red">📋</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="active-loan-amount">₹0</div>
                                <div class="stat-label-small">Active Loan Amount</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">↩�?</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-loan-paid-back">₹0</div>
                                <div class="stat-label-small">Total Paid Back</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">📈</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-interest-paid">₹0</div>
                                <div class="stat-label-small">Total Interest Paid</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="loans-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading loans...</p>
                        </div>
                    </div>
                </div>

                <!-- Invoices Content -->

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>