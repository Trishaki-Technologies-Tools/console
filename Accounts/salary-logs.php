<?php
$page_title = "Salary Logs";
$current_page = "salary-logs";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="salary-logs-page">
                    <div class="section-header">
                        <div>
                            <h2>Salary Logs & Payroll</h2>
                            <p class="company-subtitle">Manage employee salaries and payslips</p>
                        </div>
                        <div class="filter-row" style="display: flex; gap: 10px; align-items: center;">
                            <button class="btn-secondary" onclick="openEmployeeModal()" style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>Manage Employees</span>
                            </button>
                            <button class="btn-primary" onclick="showAddSalaryModal()" style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                <span>Pay Salary</span>
                            </button>
                        </div>
                    </div>

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="salary-paid-this-month">₹0</div>
                                <div class="stat-label-small">This Month Payroll</div>
                            </div>
                        </div>

                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-salary-paid">₹0</div>
                                <div class="stat-label-small">Total Payroll Paid</div>
                            </div>
                        </div>

                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                            </div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-employees-paid">0</div>
                                <div class="stat-label-small">Employees Count</div>
                            </div>
                        </div>

                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-orange">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="salary-paid-last-month">₹0</div>
                                <div class="stat-label-small">Last Month Payroll</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section">
                        <div class="table-responsive" id="salary-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading salary records...</p>
                        </div>
                    </div>
                </div>

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>