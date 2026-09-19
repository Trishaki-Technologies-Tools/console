<?php
$page_title = "Dashboard";
$current_page = "dashboard";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="dashboard-page">
                    <!-- Profit/Loss Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="12" y1="1" x2="12" y2="23" />
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                            </svg>
                            Profit/Loss Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- This Month Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-month-profit">₹0</div>
                                    <div class="stat-label-small">This Month Profit/Loss</div>
                                </div>
                            </div>
                            <!-- Last Month Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="last-month-profit">₹0</div>
                                    <div class="stat-label-small">Last Month Profit/Loss</div>
                                </div>
                            </div>
                            <!-- This FY Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-year-profit">₹0</div>
                                    <div class="stat-label-small">This FY Profit/Loss</div>
                                </div>
                            </div>
                            <!-- Overall Profit/Loss -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="overall-profit">₹0</div>
                                    <div class="stat-label-small">Overall Profit/Loss</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Income Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg>
                            Income Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- This Month Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <line x1="2" y1="10" x2="22" y2="10" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-month-income">₹0</div>
                                    <div class="stat-label-small">This Month Income</div>
                                </div>
                            </div>
                            <!-- Last Month Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <line x1="2" y1="10" x2="22" y2="10" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="last-month-income">₹0</div>
                                    <div class="stat-label-small">Last Month Income</div>
                                </div>
                            </div>
                            <!-- This FY Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <line x1="2" y1="10" x2="22" y2="10" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-year-income">₹0</div>
                                    <div class="stat-label-small">This FY Income</div>
                                </div>
                            </div>
                            <!-- Total Income -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                        <line x1="2" y1="10" x2="22" y2="10" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-income">₹0</div>
                                    <div class="stat-label-small">Total Income</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            Expense Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- This Month Expense -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-month-expense">₹0</div>
                                    <div class="stat-label-small">This Month Expense</div>
                                </div>
                            </div>
                            <!-- Last Month Expense -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="last-month-expense">₹0</div>
                                    <div class="stat-label-small">Last Month Expense</div>
                                </div>
                            </div>
                            <!-- This FY Expense -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="this-year-expense">₹0</div>
                                    <div class="stat-label-small">This FY Expense</div>
                                </div>
                            </div>
                            <!-- Total Expenses -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-expenses">₹0</div>
                                    <div class="stat-label-small">Total Expenses</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="9" cy="7" r="4" />
                            </svg>
                            Business Operations Stats
                        </h3>
                        <div class="stats-row-small">
                            <!-- Invoices Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-invoices-count">0</div>
                                    <div class="stat-label-small">Invoices Total Count</div>
                                </div>
                            </div>
                            <!-- Vouchers Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="3" y="4" width="18" height="16" rx="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="4"></line>
                                        <line x1="8" y1="2" x2="8" y2="4"></line>
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-vouchers-count">0</div>
                                    <div class="stat-label-small">Vouchers Total Count</div>
                                </div>
                            </div>
                            <!-- Clients Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-clients-count">0</div>
                                    <div class="stat-label-small">Clients Count</div>
                                </div>
                            </div>
                            <!-- Students Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-blue">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"></path>
                                        <path d="M6 12v5c3 3 9 3 12 0v-5"></path>
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-students-count">0</div>
                                    <div class="stat-label-small">Students Count</div>
                                </div>
                            </div>
                            <!-- Quotations Total Count -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-green">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <path
                                            d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2">
                                        </path>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="total-quotations-count">0</div>
                                    <div class="stat-label-small">Quotations Total Count</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Loans Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Loans Section
                        </h3>
                        <div class="stats-row-small">
                            <!-- Active Loan Amount -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-purple">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <rect x="2" y="2" width="20" height="20" rx="2" ry="2" />
                                        <line x1="12" y1="18" x2="12.01" y2="18" />
                                        <rect x="6" y="6" width="12" height="8" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="active-loan-amount">₹0</div>
                                    <div class="stat-label-small">Active Loan Amount</div>
                                </div>
                            </div>
                            <!-- Interest Paid Till Date -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <line x1="12" y1="1" x2="12" y2="23" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="interest-paid-total">₹0</div>
                                    <div class="stat-label-small">Interest Paid Till Date</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balances Section -->
                    <div class="dashboard-section">
                        <h3 class="section-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5">
                                <rect x="2" y="22" width="20" height="2"></rect>
                                <path d="M4 22V10l8-6 8 6v12M18 22H6M12 10v12M9 14v8M15 14v8"></path>
                            </svg>
                            Balances Section
                        </h3>
                        <div class="stats-row-small" id="balances-grid">
                            <!-- Total Available Balance Card -->
                            <div class="stat-card-small">
                                <div class="stat-icon-small icon-cyan">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2">
                                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                                    </svg>
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small" id="balance">₹0</div>
                                    <div class="stat-label-small">Total Available Balance</div>
                                </div>
                            </div>
                            <!-- Placeholder for Payment Method Cards -->
                            <div id="payment-balances-row" style="display: contents;">
                                <!-- Dynamically loaded from JS -->
                            </div>
                        </div>
                    </div>
                    </div>
                 <!-- Dashboard Content End -->



<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>