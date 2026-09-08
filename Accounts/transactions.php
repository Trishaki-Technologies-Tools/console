<?php
$page_title = "Transactions";
$current_page = "transactions";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="transactions-page">
                    <div class="section-header" style="margin-bottom: 20px;">
                        <div>
                            <h2>Financial Transactions Ledger</h2>
                            <p class="company-subtitle">Unified Income & Expenses Ledger</p>
                        </div>
                        <div class="filter-row" style="gap: 12px;">
                            <!-- Tab Options -->
                            <div class="ledger-tabs"
                                style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px;">
                                <button id="txn-tab-income" class="ledger-tab-btn active"
                                    onclick="switchLedgerTab('income')">Incomes</button>
                                <button id="txn-tab-expense" class="ledger-tab-btn"
                                    onclick="switchLedgerTab('expense')">Expenses</button>
                            </div>
                            <button class="btn-primary" id="btn-add-income" onclick="openAddIncomeModal()">+ Add
                                Income</button>
                            <button class="btn-primary" id="btn-add-expense" onclick="openAddExpenseModal()"
                                style="display: none; background: var(--danger);">+ Add Expense</button>
                        </div>
                    </div>

                    <!-- Filter Controls Section -->
                    <div class="records-section"
                        style="margin-bottom: 20px; padding: 16px; border: 1px solid var(--border-light); border-radius: 12px; background: var(--bg-card);">
                        <div
                            style="display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
                            <!-- Left: Search and Filters -->
                            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                                <input type="text" id="txn-search" class="form-input"
                                    placeholder="Search description, category..." oninput="onTxnFilterChange()"
                                    style="max-width: 250px; min-width: 200px;">
                                <div class="ledger-period-filters"
                                    style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-left: 10px;">
                                    <a href="#" class="period-filter-link active" data-period="this-month"
                                        onclick="selectPeriod(event, 'this-month')">This Month</a>
                                    <a href="#" class="period-filter-link" data-period="last-month"
                                        onclick="selectPeriod(event, 'last-month')">Last Month</a>
                                    <a href="#" class="period-filter-link" data-period="this-fy"
                                        onclick="selectPeriod(event, 'this-fy')">This FY</a>
                                    <a href="#" class="period-filter-link" data-period="last-fy"
                                        onclick="selectPeriod(event, 'last-fy')">Last FY</a>
                                    <a href="#" class="period-filter-link" data-period="total"
                                        onclick="selectPeriod(event, 'total')">All time</a>
                                    <a href="#" class="period-filter-link" data-period="specific-date"
                                        onclick="selectPeriod(event, 'specific-date')">Specific Date</a>
                                    <a href="#" class="period-filter-link" data-period="date-range"
                                        onclick="selectPeriod(event, 'date-range')">Date Range</a>
                                </div>

                                <!-- Specific Date Picker (Hidden by default) -->
                                <input type="date" id="txn-specific-date" class="form-input"
                                    onchange="onTxnFilterChange()" style="display: none; max-width: 150px;">

                                <!-- Date Range Pickers (Hidden by default) -->
                                <div id="txn-date-range-container"
                                    style="display: none; align-items: center; gap: 8px;">
                                    <input type="date" id="txn-start-date" class="form-input"
                                        onchange="onTxnFilterChange()" style="max-width: 140px;">
                                    <span style="color: #64748b; font-size: 14px;">to</span>
                                    <input type="date" id="txn-end-date" class="form-input"
                                        onchange="onTxnFilterChange()" style="max-width: 140px;">
                                </div>
                            </div>

                            <!-- Right: Category Manage -->
                            <div style="display: flex; gap: 10px;">
                                <button id="btn-manage-income-cats" class="btn-secondary"
                                    onclick="openCategoryModal()">📊 Manage Categories</button>
                                <button id="btn-manage-expense-cats" class="btn-secondary"
                                    onclick="openExpenseCategoryModal()" style="display: none;">📊 Manage
                                    Categories</button>
                            </div>
                        </div>
                    </div>

                    <!-- Ledger Totals Bar -->
                    <div class="stats-row-small" style="margin-bottom: 20px;">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">💰</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="txn-total-sum">₹0.00</div>
                                <div class="stat-label-small" id="txn-total-label">Total Incomes Value</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-purple">🔢</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="txn-total-count">0</div>
                                <div class="stat-label-small" id="txn-count-label">Incomes Count</div>
                            </div>
                        </div>
                    </div>

                    <!-- List container -->
                    <div class="records-section">
                        <div class="table-responsive" id="txn-table-container">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading transactions...</p>
                        </div>
                    </div>
                </div>

                <!-- Salary Logs Content -->

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>