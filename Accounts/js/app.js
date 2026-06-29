// Check if running from file protocol
if (window.location.protocol === 'file:') {
    alert('⚠️ You are opening this file directly! \n\nDynamic features like Database and APIs will NOT work.\nPlease open this project through your local server (XAMPP/WAMP) at:\nhttp://localhost/Accounts/');
}

// ── Custom Popup Utilities ──
function showConfirmPopup(title, message, onConfirm) {
    const overlay = document.createElement('div');
    overlay.className = 'popup-overlay';
    overlay.innerHTML = `
        <div class="popup-box">
            <div class="popup-icon">⚠️</div>
            <div class="popup-title">${title}</div>
            <div class="popup-msg">${message}</div>
            <div class="popup-actions">
                <button class="popup-btn cancel" id="popupCancel">Cancel</button>
                <button class="popup-btn danger" id="popupConfirm">Delete</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('#popupCancel').onclick = () => overlay.remove();
    overlay.querySelector('#popupConfirm').onclick = () => { overlay.remove(); onConfirm(); };
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

function showAlertPopup(title, message, type) {
    const icon = type === 'success' ? '✅' : '❌';
    const btnClass = type === 'success' ? 'ok' : 'danger';
    const overlay = document.createElement('div');
    overlay.className = 'popup-overlay';
    overlay.innerHTML = `
        <div class="popup-box">
            <div class="popup-icon">${icon}</div>
            <div class="popup-title">${title}</div>
            <div class="popup-msg">${message}</div>
            <div class="popup-actions">
                <button class="popup-btn ${btnClass}" id="popupOk">OK</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);
    overlay.querySelector('#popupOk').onclick = () => overlay.remove();
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

// Navigation handling with Persistence
function switchPage(pageId, title = null) {
    if (!pageId) return;
    
    // Hide all pages
    document.querySelectorAll('.page-content').forEach(content => content.classList.add('hidden'));
    
    // Show selected page
    const pageElement = document.getElementById(pageId + '-page');
    if (pageElement) {
        pageElement.classList.remove('hidden');
    } else {
        // Fallback to dashboard if page doesn't exist
        document.getElementById('dashboard-page').classList.remove('hidden');
        pageId = 'dashboard';
    }

    // Update active class in menu
    document.querySelectorAll('.nav-item, .dropdown-item').forEach(nav => {
        if (nav.getAttribute('data-page') === pageId) {
            nav.classList.add('active');
            if (!title) title = nav.textContent.trim();
        } else {
            nav.classList.remove('active');
        }
    });

    if (title) {
        document.getElementById('page-title').textContent = title;
    }

    // Save to memory
    localStorage.setItem('activeAccountsPage', pageId);

    // Load page-specific data if functions exist
    if (pageId === 'dashboard') {
        loadDashboardData();
        loadReports();
    }
    if (pageId === 'transactions') loadTransactions();
    if (pageId === 'salary-logs') loadSalaryLogs();
    if (pageId === 'loans') loadLoans();
    if (pageId === 'invoices') {
        if (typeof loadInvoicesFromDB === 'function') loadInvoicesFromDB();
    }
    if (pageId === 'voucher') {
        if (typeof loadVouchers === 'function') loadVouchers();
    }
    if (pageId === 'clients') loadClients();
    if (pageId === 'quotations') loadQuotations();
    if (pageId === 'audit-logs') loadAuditLogs();
    if (pageId === 'settings') loadSettings();
}

document.querySelectorAll('.nav-item, .dropdown-item').forEach(item => {
    item.addEventListener('click', function (e) {
        const page = this.getAttribute('data-page');
        if (!page) return; // Dropdown parent
        e.preventDefault();
        switchPage(page, this.textContent.trim());
    });
});

// Toggle Sidebar collapsible state
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('collapsed');
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
    }
}

// Load dashboard data on page load
function initApp() {
    console.log("Initializing App...");
    
    // Sidebar state loading
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.add('collapsed');
    }

    const savedPage = localStorage.getItem('activeAccountsPage') || 'dashboard';
    
    // Switch to page first (loads active page data on demand)
    switchPage(savedPage);
    
    if (typeof setInitialDates === 'function') setInitialDates();

    setInterval(removeBadgeElements, 1000); 
    
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList') {
                removeBadgeElements();
            }
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

// Ensure init runs AFTER all scripts are loaded
window.addEventListener('load', initApp);

// Filter incomes based on selected report
function filterIncomes() {
    const filter = document.getElementById('income-filter').value;
    console.log('Filtering incomes with:', filter); // Debug log

    const url = filter === 'all' ? 'api/incomes.php' : `api/filter_incomes.php?filter=${filter}`;

    fetch(url)
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                console.log('Filtered income data:', data); // Debug log
                displayIncomes(data);
            } catch (e) {
                console.error('Filter Incomes Error:', text);
                alert('Error filtering incomes. Database connection issue.');
            }
        })
        .catch(error => {
            console.error('Error filtering incomes:', error);
            alert('Error filtering incomes. Please try again.');
        });
}

// Filter expenses based on selected report
function filterExpenses() {
    const filter = document.getElementById('expense-filter').value;
    console.log('Filtering expenses with:', filter); // Debug log

    const url = filter === 'all' ? 'api/expenses.php' : `api/filter_expenses.php?filter=${filter}`;

    fetch(url)
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                console.log('Filtered expense data:', data); // Debug log
                displayExpenses(data);
            } catch (e) {
                console.error('Filter Expenses Error:', text);
                alert('Error filtering expenses. Database connection issue.');
            }
        })
        .catch(error => {
            console.error('Error filtering expenses:', error);
            alert('Error filtering expenses. Please try again.');
        });
}

// Load dashboard statistics
// Load dashboard statistics
function loadDashboardData() {
    fetch('api/dashboard.php')
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                
                // 1. Balances Section
                document.getElementById('balance').textContent = '₹' + data.total_balance;
                
                const modeIcons = {
                    'Cash': { icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2"></circle><path d="M6 12h.01M18 12h.01"></path></svg>`, colorClass: 'icon-green' },
                    'Bank Transfer': { icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="22" width="20" height="2"></rect><path d="M4 22V10l8-6 8 6v12M18 22H6M12 10v12M9 14v8M15 14v8"></path></svg>`, colorClass: 'icon-blue' },
                    'Cheque': { icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="16" y1="2" x2="16" y2="4"></line><line x1="8" y1="2" x2="8" y2="4"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`, colorClass: 'icon-purple' },
                    'UPI': { icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>`, colorClass: 'icon-cyan' }
                };
                const defaultIcon = { icon: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" /></svg>`, colorClass: 'icon-cyan' };
                
                let paymentHtml = '';
                if (data.payment_balances && Array.isArray(data.payment_balances)) {
                    data.payment_balances.forEach(pb => {
                        const config = modeIcons[pb.name] || defaultIcon;
                        paymentHtml += `
                            <div class="stat-card-small">
                                <div class="stat-icon-small ${config.colorClass}">
                                    ${config.icon}
                                </div>
                                <div class="stat-info-small">
                                    <div class="stat-value-small">₹${pb.balance}</div>
                                    <div class="stat-label-small">${pb.name} Balance</div>
                                </div>
                            </div>
                        `;
                    });
                }
                document.getElementById('payment-balances-row').innerHTML = paymentHtml;

                // 2. Income Section
                document.getElementById('this-month-income').textContent = '₹' + data.this_month_income;
                document.getElementById('last-month-income').textContent = '₹' + data.last_month_income;
                document.getElementById('this-year-income').textContent = '₹' + data.this_year_income;
                document.getElementById('total-income').textContent = '₹' + data.total_income;

                // 3. Expense Section
                document.getElementById('this-month-expense').textContent = '₹' + data.this_month_expense;
                document.getElementById('last-month-expense').textContent = '₹' + data.last_month_expense;
                document.getElementById('this-year-expense').textContent = '₹' + data.this_year_expense;
                document.getElementById('total-expenses').textContent = '₹' + data.total_expenses;

                // 4. Profit/Loss Section
                document.getElementById('this-month-profit').textContent = '₹' + data.this_month_profit;
                document.getElementById('last-month-profit').textContent = '₹' + data.last_month_profit;
                document.getElementById('this-year-profit').textContent = '₹' + data.this_year_profit;
                document.getElementById('overall-profit').textContent = '₹' + data.overall_profit;

                // 5. Loans Section
                document.getElementById('active-loan-amount').textContent = '₹' + data.active_loans_amount;
                document.getElementById('interest-paid-total').textContent = '₹' + data.interest_paid_total;

                // 6. Business Operations Stats
                document.getElementById('total-invoices-count').textContent = data.total_invoices_count;
                document.getElementById('total-vouchers-count').textContent = data.total_vouchers_count;
                document.getElementById('total-clients-count').textContent = data.total_clients_count;
                document.getElementById('total-quotations-count').textContent = data.total_quotations_count;
                document.getElementById('total-employees-count').textContent = data.total_employees_count;

            } catch (e) {
                console.error('Dashboard Error:', text);
                document.getElementById('balance').textContent = '₹0';
                document.getElementById('payment-balances-row').innerHTML = '';
                
                document.getElementById('this-month-income').textContent = '₹0';
                document.getElementById('last-month-income').textContent = '₹0';
                document.getElementById('this-year-income').textContent = '₹0';
                document.getElementById('total-income').textContent = '₹0';

                document.getElementById('this-month-expense').textContent = '₹0';
                document.getElementById('last-month-expense').textContent = '₹0';
                document.getElementById('this-year-expense').textContent = '₹0';
                document.getElementById('total-expenses').textContent = '₹0';

                document.getElementById('this-month-profit').textContent = '₹0';
                document.getElementById('last-month-profit').textContent = '₹0';
                document.getElementById('this-year-profit').textContent = '₹0';
                document.getElementById('overall-profit').textContent = '₹0';

                document.getElementById('active-loan-amount').textContent = '₹0';
                document.getElementById('interest-paid-total').textContent = '₹0';

                document.getElementById('total-invoices-count').textContent = '0';
                document.getElementById('total-vouchers-count').textContent = '0';
                document.getElementById('total-clients-count').textContent = '0';
                document.getElementById('total-quotations-count').textContent = '0';
                document.getElementById('total-employees-count').textContent = '0';
            }
        })
        .catch(error => console.error('Network Error:', error));
}

// Load incomes
function loadIncomes() {
    const filter = document.getElementById('income-filter') ? document.getElementById('income-filter').value : 'all';
    const url = filter && filter !== 'all' ? `api/filter_incomes.php?filter=${filter}` : 'api/incomes.php';

    fetch(url)
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                displayIncomes(data);
            } catch (e) {
                console.error('Incomes Error:', text);
                displayIncomes([]);
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            displayIncomes([]);
        });
}

// Display incomes in table
function displayIncomes(incomes) {
    const container = document.getElementById('income-list');
    if (!container) return;
    if (incomes.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No incomes found. Add your first income!</p>';
        return;
    }

    let html = `<table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Date</th>
                <th>Description</th>
                <th>Category Type</th>
                <th>Payment Mode</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;

    incomes.forEach((income, index) => {
        let attachmentHtml = '';
        if (income.attachment) {
            attachmentHtml = ` <a href="${income.attachment}" target="_blank" title="View Attachment" style="display: inline-flex; align-items: center; justify-content: center; color: #6366f1; margin-left: 6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg></a>`;
        }
        html += `<tr>
            <td>${index + 1}</td>
            <td>${income.date}</td>
            <td>${income.description}${attachmentHtml}</td>
            <td><span class="category-badge">${income.category || 'Other'}</span></td>
            <td>${income.payment_mode || 'Cash'}</td>
            <td style="font-weight: 600; color: #10b981;">₹${parseFloat(income.amount).toFixed(2)}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editIncome(${income.id})" title="Edit"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                <button class="btn-action btn-delete-small" onclick="deleteIncome(${income.id})" title="Delete"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;

    // Update stats
    updateIncomeStats(incomes);
}

// Update income statistics
// Update income statistics
function updateIncomeStats(incomes) {
    // 1. Total Stats
    const totalCount = incomes.length;
    const totalAmount = incomes.reduce((sum, inc) => sum + parseFloat(inc.amount), 0);

    // 2. This Month Stats
    const now = new Date();
    const currentMonth = now.getMonth(); // 0-11
    const currentYear = now.getFullYear();

    const thisMonthIncomes = incomes.filter(inc => {
        const d = new Date(inc.date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    });

    const monthCount = thisMonthIncomes.length;
    const monthAmount = thisMonthIncomes.reduce((sum, inc) => sum + parseFloat(inc.amount), 0);

    // 3. Update UI
    const totalAmountEl = document.getElementById('inc-total-amount');
    const totalCountEl = document.getElementById('inc-total-count');
    const monthAmountEl = document.getElementById('inc-month-amount');
    const monthCountEl = document.getElementById('inc-month-count');

    // Check if elements exist (in case user is on a different page or partial load)
    if (totalAmountEl) totalAmountEl.textContent = '₹' + totalAmount.toFixed(2);
    if (totalCountEl) totalCountEl.textContent = totalCount;
    if (monthAmountEl) monthAmountEl.textContent = '₹' + monthAmount.toFixed(2);
    if (monthCountEl) monthCountEl.textContent = monthCount;
}

// Load expenses
function loadExpenses() {
    const filter = document.getElementById('expense-filter') ? document.getElementById('expense-filter').value : 'all';
    const url = filter && filter !== 'all' ? `api/filter_expenses.php?filter=${filter}` : 'api/expenses.php';

    fetch(url)
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                displayExpenses(data);
            } catch (e) {
                console.error('Expenses Error:', text);
                displayExpenses([]);
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            displayExpenses([]);
        });
}

// Display expenses in table
function displayExpenses(expenses) {
    const container = document.getElementById('expense-list');
    if (!container) return;
    if (expenses.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No expenses found. Add your first expense!</p>';
        return;
    }

    let html = `<table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Date</th>
                <th>Description</th>
                <th>Category Type</th>
                <th>Payment Mode</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;

    expenses.forEach((expense, index) => {
        let attachmentHtml = '';
        if (expense.attachment) {
            attachmentHtml = ` <a href="${expense.attachment}" target="_blank" title="View Attachment" style="display: inline-flex; align-items: center; justify-content: center; color: #6366f1; margin-left: 6px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg></a>`;
        }
        html += `<tr>
            <td>${index + 1}</td>
            <td>${expense.date}</td>
            <td>${expense.description}${attachmentHtml}</td>
            <td><span class="category-badge">${expense.category || 'Other'}</span></td>
            <td>${expense.payment_mode || 'Cash'}</td>
            <td style="font-weight: 600; color: #ef4444;">₹${parseFloat(expense.amount).toFixed(2)}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editExpense(${expense.id})" title="Edit"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                <button class="btn-action btn-delete-small" onclick="deleteExpense(${expense.id})" title="Delete"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;

    // Update stats
    updateExpenseStats(expenses);
}

// Update expense statistics
// Update expense statistics
function updateExpenseStats(expenses) {
    // 1. Total Stats
    const totalCount = expenses.length;
    const totalAmount = expenses.reduce((sum, exp) => sum + parseFloat(exp.amount), 0);

    // 2. This Month Stats
    const now = new Date();
    const currentMonth = now.getMonth(); // 0-11
    const currentYear = now.getFullYear();

    const thisMonthExpenses = expenses.filter(exp => {
        const d = new Date(exp.date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    });

    const monthCount = thisMonthExpenses.length;
    const monthAmount = thisMonthExpenses.reduce((sum, exp) => sum + parseFloat(exp.amount), 0);

    // 3. Update UI
    const totalAmountEl = document.getElementById('exp-total-amount');
    const totalCountEl = document.getElementById('exp-total-count');
    const monthAmountEl = document.getElementById('exp-month-amount');
    const monthCountEl = document.getElementById('exp-month-count');

    if (totalAmountEl) totalAmountEl.textContent = '₹' + totalAmount.toFixed(2);
    if (totalCountEl) totalCountEl.textContent = totalCount;
    if (monthAmountEl) monthAmountEl.textContent = '₹' + monthAmount.toFixed(2);
    if (monthCountEl) monthCountEl.textContent = monthCount;
}

// Add income form
function showAddIncomeForm() {
    // Load available reports and categories
    Promise.all([
        fetch('api/reports.php?type=monthly').then(r => r.json()),
        fetch('api/income_categories.php').then(r => r.json())
    ])
        .then(([reports, categories]) => {
            if (reports.length === 0) {
                alert('⚠️ No reports available!\n\nReports are automatically created on the 1st of each month. Please wait for a report to be created or contact administrator.');
                return;
            }

            populateIncomeReportDropdown(reports);
            populateIncomeCategoryDropdown(categories);
            openAddIncomeModal();
        })
        .catch(error => console.error('Error:', error));
}

// Populate income report dropdown
function populateIncomeReportDropdown(reports) {
    const select = document.getElementById('incomeReportSelect');
    if (!select) return;
    let html = '<option value="">Please select a report first</option>';

    reports.forEach(report => {
        html += `<option value="${report.period}">${report.period}</option>`;
    });

    select.innerHTML = html;
}

// Update income date range when report is selected
function updateIncomeDateRange() {
    const reportSelect = document.getElementById('incomeReportSelect');
    const selectedReport = reportSelect.value;

    if (!selectedReport) {
        // Disable all fields
        document.getElementById('incomeDate').disabled = true;
        document.getElementById('incomeDescription').disabled = true;
        document.getElementById('incomeCategorySelect').disabled = true;
        document.getElementById('incomePaymentMode').disabled = true;
        document.getElementById('incomeAmount').disabled = true;
        document.getElementById('incomeDateHint').textContent = 'Please select a report first';
        return;
    }

    // Enable all fields
    document.getElementById('incomeDate').disabled = false;
    document.getElementById('incomeDescription').disabled = false;
    document.getElementById('incomeCategorySelect').disabled = false;
    document.getElementById('incomePaymentMode').disabled = false;
    document.getElementById('incomeAmount').disabled = false;

    // Parse the selected month (e.g., "JAN-2026")
    const [monthStr, year] = selectedReport.split('-');
    const monthMap = {
        'JAN': 0, 'FEB': 1, 'MAR': 2, 'APR': 3, 'MAY': 4, 'JUN': 5,
        'JUL': 6, 'AUG': 7, 'SEP': 8, 'OCT': 9, 'NOV': 10, 'DEC': 11
    };

    const month = monthMap[monthStr];
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Format dates for input
    const firstDayStr = firstDay.toISOString().split('T')[0];
    const lastDayStr = lastDay.toISOString().split('T')[0];

    // Set date constraints
    const dateInput = document.getElementById('incomeDate');
    dateInput.min = firstDayStr;
    dateInput.max = lastDayStr;
    dateInput.value = firstDayStr;

    document.getElementById('incomeDateHint').textContent = `Select a date between ${firstDayStr} and ${lastDayStr}`;
}

// Populate income category dropdown
function populateIncomeCategoryDropdown(categories) {
    const select = document.getElementById('incomeCategorySelect');
    let html = '<option value="">Select Category</option>';

    categories.forEach(category => {
        html += `<option value="${category.category_name}">${category.category_name}</option>`;
    });

    select.innerHTML = html;
}

// Open add income modal
function openAddIncomeModal() {
    // Set default date-time to now in local timezone
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('incomeDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;

    // Load categories
    fetch('api/income_categories.php')
        .then(r => r.json())
        .then(categories => {
            populateIncomeCategoryDropdown(categories);
        })
        .catch(err => console.error('Error loading income categories:', err));

    // Load payment modes
    loadPaymentModesDropdowns();

    document.getElementById('addIncomeModal').classList.add('show');
}

// Close add income modal
function closeAddIncomeModal() {
    document.getElementById('addIncomeModal').classList.remove('show');
    document.getElementById('incomeForm').reset();
}

// Submit income form
function submitIncome(event) {
    event.preventDefault();

    const description = document.getElementById('incomeDescription').value;
    const amount = document.getElementById('incomeAmount').value;
    const category = document.getElementById('incomeCategorySelect').value;
    const paymentMode = document.getElementById('incomePaymentMode').value;
    const date = document.getElementById('incomeDate').value;

    addIncomeWithDate(description, amount, category, paymentMode, date);
}

// Add income via AJAX with date
function addIncomeWithDate(description, amount, category, paymentMode, date) {
    const formData = new FormData();
    formData.append('description', description);
    formData.append('amount', amount);
    formData.append('category', category);
    formData.append('payment_mode', paymentMode);
    formData.append('date', date);

    const attachmentInput = document.getElementById('incomeAttachment');
    if (attachmentInput && attachmentInput.files.length > 0) {
        formData.append('attachment', attachmentInput.files[0]);
    }

    fetch('api/add_income.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeAddIncomeModal();
                loadIncomes();
                loadDashboardData();
                loadReports();
                if (typeof loadTransactions === 'function') loadTransactions();
            } else {
                showAlertPopup('Error', data.error || 'Failed to add income', 'error');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Add income via AJAX
function addIncome(description, amount, category, paymentMode) {
    const formData = new FormData();
    formData.append('description', description);
    formData.append('amount', amount);
    formData.append('category', category);
    formData.append('payment_mode', paymentMode);

    fetch('api/add_income.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadIncomes();
                loadDashboardData();
                if (typeof loadTransactions === 'function') loadTransactions();
            }
        })
        .catch(error => console.error('Error:', error));
}

// Edit income
function editIncome(id) {
    Promise.all([
        fetch(`api/get_income.php?id=${id}`).then(r => r.json()),
        fetch('api/income_categories.php').then(r => r.json()),
        fetch('api/get_payment_modes.php').then(r => r.json())
    ])
        .then(([income, categories, modes]) => {
            if (income.error) {
                showAlertPopup('Error', income.error, 'error');
                return;
            }

            // Populate details
            document.getElementById('editIncomeId').value = income.id;
            
            // Format datetime local
            let datetimeVal = income.date;
            if (income.created_at) {
                const timePart = income.created_at.split(' ')[1];
                if (timePart) {
                    datetimeVal += 'T' + timePart.substring(0, 5);
                } else {
                    datetimeVal += 'T12:00';
                }
            } else {
                datetimeVal += 'T12:00';
            }
            document.getElementById('editIncomeDate').value = datetimeVal;
            
            document.getElementById('editIncomeDescription').value = income.description;
            document.getElementById('editIncomeAmount').value = income.amount;

            // Populate categories
            const select = document.getElementById('editIncomeCategorySelect');
            let html = '<option value="">Select Category</option>';
            categories.forEach(cat => {
                const selected = Number(cat.id) === Number(income.category_id) ? 'selected' : '';
                html += `<option value="${cat.category_name}" ${selected}>${cat.category_name}</option>`;
            });
            select.innerHTML = html;

            // Populate payment modes
            const pmSelect = document.getElementById('editIncomePaymentMode');
            let pmHtml = '<option value="">Select Payment Mode</option>';
            modes.forEach(mode => {
                const selected = Number(mode.id) === Number(income.payment_mode_id) ? 'selected' : '';
                pmHtml += `<option value="${mode.mode_name}" ${selected}>${mode.mode_name}</option>`;
            });
            pmSelect.innerHTML = pmHtml;

            // Attachment hint
            const hintEl = document.getElementById('editIncomeAttachmentHint');
            if (income.attachment) {
                hintEl.innerHTML = `Current: <a href="${income.attachment}" target="_blank" style="color: #6366f1; text-decoration: underline;">${income.attachment.split('/').pop()}</a>`;
            } else {
                hintEl.innerHTML = 'No attachment uploaded';
            }

            // Show modal
            document.getElementById('editIncomeModal').classList.add('show');
        })
        .catch(error => console.error('Error:', error));
}

function closeEditIncomeModal() {
    document.getElementById('editIncomeModal').classList.remove('show');
    document.getElementById('editIncomeForm').reset();
    document.getElementById('editIncomeAttachmentHint').innerHTML = '';
}

function submitEditIncome(event) {
    event.preventDefault();

    const id = document.getElementById('editIncomeId').value;
    const date = document.getElementById('editIncomeDate').value;
    const description = document.getElementById('editIncomeDescription').value;
    const category = document.getElementById('editIncomeCategorySelect').value;
    const paymentMode = document.getElementById('editIncomePaymentMode').value;
    const amount = document.getElementById('editIncomeAmount').value;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('date', date);
    formData.append('description', description);
    formData.append('category', category);
    formData.append('payment_mode', paymentMode);
    formData.append('amount', amount);

    const attachmentInput = document.getElementById('editIncomeAttachment');
    if (attachmentInput && attachmentInput.files.length > 0) {
        formData.append('attachment', attachmentInput.files[0]);
    }

    fetch('api/edit_income.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeEditIncomeModal();
                loadIncomes();
                loadDashboardData();
                loadReports();
                if (typeof loadTransactions === 'function') loadTransactions();
            } else {
                showAlertPopup('Error', data.error || 'Failed to update income', 'error');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Delete income
function deleteIncome(id) {
    showConfirmPopup(
        'Delete Income',
        'Are you sure you want to delete this income entry?',
        () => {
            fetch('api/delete_income.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlertPopup('Deleted', 'Income entry has been deleted successfully.', 'success');
                        loadIncomes();
                        loadDashboardData();
                        if (typeof loadTransactions === 'function') loadTransactions();
                    } else {
                        showAlertPopup('Error', data.error || 'Failed to delete income', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlertPopup('Error', 'Network error occurred.', 'error');
                });
        }
    );
}

// Add expense form
function showAddExpenseForm() {
    // Load available reports and categories
    Promise.all([
        fetch('api/reports.php?type=monthly').then(r => r.json()),
        fetch('api/expense_categories.php').then(r => r.json())
    ])
        .then(([reports, categories]) => {
            if (reports.length === 0) {
                alert('⚠️ No reports available!\n\nReports are automatically created on the 1st of each month. Please wait for a report to be created or contact administrator.');
                return;
            }

            populateExpenseReportDropdown(reports);
            populateExpenseCategoryDropdown(categories);
            openAddExpenseModal();
        })
        .catch(error => console.error('Error:', error));
}

// Populate expense report dropdown
function populateExpenseReportDropdown(reports) {
    const select = document.getElementById('expenseReportSelect');
    if (!select) return;
    let html = '<option value="">Please select a report first</option>';

    reports.forEach(report => {
        html += `<option value="${report.period}">${report.period}</option>`;
    });

    select.innerHTML = html;
}

// Update expense date range when report is selected
function updateExpenseDateRange() {
    const reportSelect = document.getElementById('expenseReportSelect');
    const selectedReport = reportSelect.value;

    if (!selectedReport) {
        // Disable all fields
        document.getElementById('expenseDate').disabled = true;
        document.getElementById('expenseDescription').disabled = true;
        document.getElementById('expenseCategorySelect').disabled = true;
        document.getElementById('expensePaymentMode').disabled = true;
        document.getElementById('expenseAmount').disabled = true;
        document.getElementById('expenseDateHint').textContent = 'Please select a report first';
        return;
    }

    // Enable all fields
    document.getElementById('expenseDate').disabled = false;
    document.getElementById('expenseDescription').disabled = false;
    document.getElementById('expenseCategorySelect').disabled = false;
    document.getElementById('expensePaymentMode').disabled = false;
    document.getElementById('expenseAmount').disabled = false;

    // Parse the selected month (e.g., "JAN-2026")
    const [monthStr, year] = selectedReport.split('-');
    const monthMap = {
        'JAN': 0, 'FEB': 1, 'MAR': 2, 'APR': 3, 'MAY': 4, 'JUN': 5,
        'JUL': 6, 'AUG': 7, 'SEP': 8, 'OCT': 9, 'NOV': 10, 'DEC': 11
    };

    const month = monthMap[monthStr];
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);

    // Format dates for input
    const firstDayStr = firstDay.toISOString().split('T')[0];
    const lastDayStr = lastDay.toISOString().split('T')[0];

    // Set date constraints
    const dateInput = document.getElementById('expenseDate');
    dateInput.min = firstDayStr;
    dateInput.max = lastDayStr;
    dateInput.value = firstDayStr;

    document.getElementById('expenseDateHint').textContent = `Select a date between ${firstDayStr} and ${lastDayStr}`;
}

// Populate expense category dropdown
function populateExpenseCategoryDropdown(categories) {
    const select = document.getElementById('expenseCategorySelect');
    let html = '<option value="">Select Category</option>';

    categories.forEach(category => {
        html += `<option value="${category.category_name}">${category.category_name}</option>`;
    });

    select.innerHTML = html;
}

// Open add expense modal
function openAddExpenseModal() {
    // Set default date-time to now in local timezone
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('expenseDate').value = `${year}-${month}-${day}T${hours}:${minutes}`;

    // Load categories
    fetch('api/expense_categories.php')
        .then(r => r.json())
        .then(categories => {
            populateExpenseCategoryDropdown(categories);
        })
        .catch(err => console.error('Error loading expense categories:', err));

    // Load payment modes
    loadPaymentModesDropdowns();

    document.getElementById('addExpenseModal').classList.add('show');
}

// Close add expense modal
function closeAddExpenseModal() {
    document.getElementById('addExpenseModal').classList.remove('show');
    document.getElementById('expenseForm').reset();
}

// Submit expense form
function submitExpense(event) {
    event.preventDefault();

    const description = document.getElementById('expenseDescription').value;
    const amount = document.getElementById('expenseAmount').value;
    const category = document.getElementById('expenseCategorySelect').value;
    const paymentMode = document.getElementById('expensePaymentMode').value;
    const date = document.getElementById('expenseDate').value;

    addExpenseWithDate(description, amount, category, paymentMode, date);
}

// Add expense via AJAX with date
function addExpenseWithDate(description, amount, category, paymentMode, date) {
    const formData = new FormData();
    formData.append('description', description);
    formData.append('amount', amount);
    formData.append('category', category);
    formData.append('payment_mode', paymentMode);
    formData.append('date', date);

    const attachmentInput = document.getElementById('expenseAttachment');
    if (attachmentInput && attachmentInput.files.length > 0) {
        formData.append('attachment', attachmentInput.files[0]);
    }

    fetch('api/add_expense.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeAddExpenseModal();
                loadExpenses();
                loadDashboardData();
                loadReports();
                if (typeof loadTransactions === 'function') loadTransactions();
            } else {
                showAlertPopup('Error', data.error || 'Failed to add expense', 'error');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Add expense via AJAX
function addExpense(description, amount, category, paymentMode) {
    const formData = new FormData();
    formData.append('description', description);
    formData.append('amount', amount);
    formData.append('category', category);
    formData.append('payment_mode', paymentMode);

    fetch('api/add_expense.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadExpenses();
                loadDashboardData();
                if (typeof loadTransactions === 'function') loadTransactions();
            }
        })
        .catch(error => console.error('Error:', error));
}

// Edit expense
function editExpense(id) {
    Promise.all([
        fetch(`api/get_expense.php?id=${id}`).then(r => r.json()),
        fetch('api/expense_categories.php').then(r => r.json()),
        fetch('api/get_payment_modes.php').then(r => r.json())
    ])
        .then(([expense, categories, modes]) => {
            if (expense.error) {
                showAlertPopup('Error', expense.error, 'error');
                return;
            }

            // Populate details
            document.getElementById('editExpenseId').value = expense.id;
            
            // Format datetime local
            let datetimeVal = expense.date;
            if (expense.created_at) {
                const timePart = expense.created_at.split(' ')[1];
                if (timePart) {
                    datetimeVal += 'T' + timePart.substring(0, 5);
                } else {
                    datetimeVal += 'T12:00';
                }
            } else {
                datetimeVal += 'T12:00';
            }
            document.getElementById('editExpenseDate').value = datetimeVal;
            
            document.getElementById('editExpenseDescription').value = expense.description;
            document.getElementById('editExpenseAmount').value = expense.amount;

            // Populate categories
            const select = document.getElementById('editExpenseCategorySelect');
            let html = '<option value="">Select Category</option>';
            categories.forEach(cat => {
                const selected = Number(cat.id) === Number(expense.category_id) ? 'selected' : '';
                html += `<option value="${cat.category_name}" ${selected}>${cat.category_name}</option>`;
            });
            select.innerHTML = html;

            // Populate payment modes
            const pmSelect = document.getElementById('editExpensePaymentMode');
            let pmHtml = '<option value="">Select Payment Mode</option>';
            modes.forEach(mode => {
                const selected = Number(mode.id) === Number(expense.payment_mode_id) ? 'selected' : '';
                pmHtml += `<option value="${mode.mode_name}" ${selected}>${mode.mode_name}</option>`;
            });
            pmSelect.innerHTML = pmHtml;

            // Attachment hint
            const hintEl = document.getElementById('editExpenseAttachmentHint');
            if (expense.attachment) {
                hintEl.innerHTML = `Current: <a href="${expense.attachment}" target="_blank" style="color: #6366f1; text-decoration: underline;">${expense.attachment.split('/').pop()}</a>`;
            } else {
                hintEl.innerHTML = 'No attachment uploaded';
            }

            // Show modal
            document.getElementById('editExpenseModal').classList.add('show');
        })
        .catch(error => console.error('Error:', error));
}

function closeEditExpenseModal() {
    document.getElementById('editExpenseModal').classList.remove('show');
    document.getElementById('editExpenseForm').reset();
    document.getElementById('editExpenseAttachmentHint').innerHTML = '';
}

function submitEditExpense(event) {
    event.preventDefault();

    const id = document.getElementById('editExpenseId').value;
    const date = document.getElementById('editExpenseDate').value;
    const description = document.getElementById('editExpenseDescription').value;
    const category = document.getElementById('editExpenseCategorySelect').value;
    const paymentMode = document.getElementById('editExpensePaymentMode').value;
    const amount = document.getElementById('editExpenseAmount').value;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('date', date);
    formData.append('description', description);
    formData.append('category', category);
    formData.append('payment_mode', paymentMode);
    formData.append('amount', amount);

    const attachmentInput = document.getElementById('editExpenseAttachment');
    if (attachmentInput && attachmentInput.files.length > 0) {
        formData.append('attachment', attachmentInput.files[0]);
    }

    fetch('api/edit_expense.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeEditExpenseModal();
                loadExpenses();
                loadDashboardData();
                loadReports();
                if (typeof loadTransactions === 'function') loadTransactions();
            } else {
                showAlertPopup('Error', data.error || 'Failed to update expense', 'error');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Delete expense
function deleteExpense(id) {
    showConfirmPopup(
        'Delete Expense',
        'Are you sure you want to delete this expense entry?',
        () => {
            fetch('api/delete_expense.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlertPopup('Deleted', 'Expense entry has been deleted successfully.', 'success');
                        loadExpenses();
                        loadDashboardData();
                        if (typeof loadTransactions === 'function') loadTransactions();
                    } else {
                        showAlertPopup('Error', data.error || 'Failed to delete expense', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlertPopup('Error', 'Network error occurred.', 'error');
                });
        }
    );
}


// Load reports
// Load reports
function loadReports() {
    changeReportView();
}

// Change report view
// Change report view
function changeReportView() {
    const reportTypeEl = document.getElementById('report-type');
    if (!reportTypeEl) return;
    const reportType = reportTypeEl.value;
    const container = document.getElementById('reports-container');
    const headerTitle = document.querySelector('#reports-page h2');
    const subtitle = document.getElementById('report-view-subtitle');

    // Update titles
    const titles = {
        'monthly': ['Monthly Reports', 'Month Wise Breakdown'],
        'quarterly': ['Quarterly Reports', 'Quarter Wise Breakdown'],
        'yearly': ['Yearly Reports', 'Year Wise Breakdown']
    };

    if (titles[reportType]) {
        headerTitle.textContent = titles[reportType][0];
        subtitle.textContent = titles[reportType][1];
    }

    // Show loading state
    container.innerHTML = '<p style="text-align:center; padding: 20px; color: #64748b;">Loading reports...</p>';

    fetch(`api/reports.php?type=${reportType}&t=${new Date().getTime()}`)
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.error) throw new Error(data.error);
                displayReports(data);
            } catch (e) {
                console.error('Reports Error:', text);
                container.innerHTML = `<p style="text-align:center; color: #ef4444; padding: 20px;">Error loading reports: Database connection issue</p>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            container.innerHTML = `<p style="text-align:center; color: #ef4444; padding: 20px;">Error loading reports: ${error.message}</p>`;
        });
}

// Display reports
function displayReports(reports) {
    const container = document.getElementById('reports-container');
    const reportType = document.getElementById('report-type').value;

    if (reports.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No reports found. Reports will be automatically created on the 1st of each month.</p>';
        return;
    }

    let html = '';
    reports.forEach(report => {
        const profit = parseFloat(report.income) - parseFloat(report.expenses);
        const profitClass = profit >= 0 ? 'positive' : 'negative';
        const isCurrentPeriod = report.is_current_period == 1;

        // Determine period label based on report type
        let periodLabel = report.period;
        if (reportType === 'quarterly') {
            periodLabel = report.period; // e.g., "Q1-2026"
        } else if (reportType === 'yearly') {
            periodLabel = report.period; // e.g., "2026"
        }

        html += `
        <div class="report-card">
            <div class="report-header">
                <div class="report-title">${periodLabel}</div>
                <div class="report-actions">
                     <button onclick="deleteReport('${report.period}')" class="btn-icon" title="Delete Report" style="background:none; border:none; cursor:pointer; font-size: 1.2rem; color: #ef4444;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                </div>
            </div>
            <div class="report-card-body">
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg></span>
                    <span class="report-stat-label">Opening Balance</span>
                    <span class="report-stat-value">₹${parseFloat(report.opening_balance).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: #10b981;"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg></span>
                    <span class="report-stat-label">Income</span>
                    <span class="report-stat-value positive">₹${parseFloat(report.income).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: #ef4444;"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg></span>
                    <span class="report-stat-label">Expenses</span>
                    <span class="report-stat-value negative">₹${parseFloat(report.expenses).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></span>
                    <span class="report-stat-label">Loan Taken</span>
                    <span class="report-stat-value" style="color: #6366f1;">₹${parseFloat(report.loan_taken || 0).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color: #10b981;"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
                    <span class="report-stat-label">Loan Paid</span>
                    <span class="report-stat-value" style="color: #10b981;">₹${parseFloat(report.loan_paid || 0).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></span>
                    <span class="report-stat-label">Profit/Loss</span>
                    <span class="report-stat-value ${profitClass}">₹${profit.toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="22" width="20" height="2"></rect><path d="M4 22V10l8-6 8 6v12M18 22H6M12 10v12M9 14v8M15 14v8"></path></svg></span>
                    <span class="report-stat-label">Closing Balance</span>
                    <span class="report-stat-value">₹${parseFloat(report.closing_balance).toFixed(2)}</span>
                </div>
            </div>
            ${isCurrentPeriod ? '<div class="report-footer">Current Period - Updates automatically</div>' : ''}
        </div>`;
    });

    container.innerHTML = html;

    // Force remove any remaining badge elements - multiple attempts
    setTimeout(() => {
        removeBadgeElements();
    }, 50);
    setTimeout(() => {
        removeBadgeElements();
    }, 500);
}

function deleteReport(month) {
    if (confirm(`Are you sure you want to delete the report for ${month}? \n\nWARNING: This will delete the report entry AND ALL income/expense records for this month. This action cannot be undone.`)) {
        fetch('api/delete_report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ month: month })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Report deleted successfully');
                    loadReports();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete report'));
                }
            })
            .catch(error => console.error('Error:', error));
    }
}

function removeBadgeElements() {
    // Remove by class names
    const badgeElements = document.querySelectorAll('.report-badges, .badge-green, .badge-red, .badge');
    badgeElements.forEach(element => {
        element.style.display = 'none !important';
        element.remove();
    });

    // Remove by content - check all elements for tick and cross symbols
    const allElements = document.querySelectorAll('*');
    allElements.forEach(element => {
        if (element.textContent === '✓' || element.textContent === '✕' ||
            element.innerHTML === '✓' || element.innerHTML === '✕' ||
            element.textContent.includes('✓') || element.textContent.includes('✕')) {
            element.style.display = 'none !important';
            element.remove();
        }
    });

    // Remove any span elements with these specific symbols
    const spans = document.querySelectorAll('span');
    spans.forEach(span => {
        if (span.textContent.trim() === '✓' || span.textContent.trim() === '✕') {
            span.style.display = 'none !important';
            span.remove();
        }
    });

    // Remove any div elements containing report-badges
    const divs = document.querySelectorAll('div');
    divs.forEach(div => {
        if (div.className && div.className.includes('report-badges')) {
            div.style.display = 'none !important';
            div.remove();
        }
    });
}
function openCategoryModal() {
    document.getElementById('categoryModal').classList.add('show');
    loadCategories();
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('show');
}

// Close modal when clicking outside
window.onclick = function (event) {
    const modal = document.getElementById('categoryModal');
    const expenseModal = document.getElementById('expenseCategoryModal');
    const addExpenseModal = document.getElementById('addExpenseModal');
    const addIncomeModal = document.getElementById('addIncomeModal');
    const paymentModal = document.getElementById('paymentModesModal');

    if (event.target == modal) {
        closeCategoryModal();
    }
    if (event.target == expenseModal) {
        closeExpenseCategoryModal();
    }
    if (event.target == addExpenseModal) {
        closeAddExpenseModal();
    }
    if (event.target == addIncomeModal) {
        closeAddIncomeModal();
    }
    if (event.target == paymentModal) {
        closePaymentModesModal();
    }
}

// Load categories
function loadCategories() {
    fetch('api/income_categories.php')
        .then(response => response.text())
        .then(text => {
            try {
                const categories = JSON.parse(text);
                displayCategories(categories);
                updateCategoryDropdown(categories);
            } catch (e) {
                console.error('Income Categories Error:', text);
                displayCategories([]);
                updateCategoryDropdown([]);
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            displayCategories([]);
            updateCategoryDropdown([]);
        });
}

// Display categories in modal
function displayCategories(categories) {
    const container = document.getElementById('categoryList');

    if (categories.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">No categories found.</p>';
        return;
    }

    let html = '';
    categories.forEach(category => {
        html += `
        <div class="category-item">
            <span class="category-name">${category.category_name}</span>
            <button class="btn-delete-icon" onclick="deleteCategory(${category.id}, '${category.category_name}')" title="Delete">✖</button>
        </div>`;
    });

    container.innerHTML = html;
}

// Update category dropdown in income form
function updateCategoryDropdown(categories) {
    const dropdown = document.getElementById('income-category');
    if (!dropdown) return;

    // Keep "All Categories" option
    let html = '<option value="all">All Categories</option>';

    categories.forEach(category => {
        html += `<option value="${category.category_name}">${category.category_name}</option>`;
    });

    dropdown.innerHTML = html;
}

// Add new category
function addCategory() {
    const input = document.getElementById('newCategoryInput');
    const categoryName = input.value.trim();

    if (!categoryName) {
        alert('Please enter a category name');
        return;
    }

    const formData = new FormData();
    formData.append('category_name', categoryName);

    fetch('api/add_income_category.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadCategories();
            } else {
                alert(data.error || 'Failed to add category');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Delete category
function deleteCategory(id, name) {
    showConfirmPopup(
        'Delete Category',
        `Are you sure you want to delete "<strong>${name}</strong>"?`,
        () => {
            fetch(`api/delete_income_category.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlertPopup('Deleted', `Category "${name}" has been deleted.`, 'success');
                        loadCategories();
                    } else {
                        showAlertPopup('Error', data.error || 'Failed to delete category', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlertPopup('Error', 'Something went wrong. Please try again.', 'error');
                });
        }
    );
}

// Load categories on page load
window.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
    loadIncomes();
    loadExpenses();
    loadReports();
    loadSalaryLogs();
    loadLoans();
    loadCategories();
    loadExpenseCategories();
    loadEmployees(); // Load employees
    setupAutoCategorization();
});


// Expense Category Management Functions
function openExpenseCategoryModal() {
    document.getElementById('expenseCategoryModal').classList.add('show');
    loadExpenseCategories();
}

function closeExpenseCategoryModal() {
    document.getElementById('expenseCategoryModal').classList.remove('show');
}

// Load expense categories
function loadExpenseCategories() {
    fetch('api/expense_categories.php')
        .then(response => response.json())
        .then(categories => {
            displayExpenseCategories(categories);
            updateExpenseCategoryDropdown(categories);
        })
        .catch(error => console.error('Error:', error));
}

// Display expense categories in modal
function displayExpenseCategories(categories) {
    const container = document.getElementById('expenseCategoryList');

    if (categories.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">No categories found.</p>';
        return;
    }

    let html = '';
    categories.forEach(category => {
        html += `
        <div class="category-item">
            <span class="category-name">${category.category_name}</span>
            <button class="btn-delete-icon" onclick="deleteExpenseCategory(${category.id}, '${category.category_name}')" title="Delete">✖</button>
        </div>`;
    });

    container.innerHTML = html;
}

// Update expense category dropdown
function updateExpenseCategoryDropdown(categories) {
    const dropdown = document.getElementById('expense-category');
    if (!dropdown) return;

    let html = '<option value="all">All Categories</option>';

    categories.forEach(category => {
        html += `<option value="${category.category_name}">${category.category_name}</option>`;
    });

    dropdown.innerHTML = html;
}

// Add new expense category
function addExpenseCategory() {
    const input = document.getElementById('newExpenseCategoryInput');
    const categoryName = input.value.trim();

    if (!categoryName) {
        alert('Please enter a category name');
        return;
    }

    const formData = new FormData();
    formData.append('category_name', categoryName);

    fetch('api/add_expense_category.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadExpenseCategories();
            } else {
                alert(data.error || 'Failed to add category');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Delete expense category
function deleteExpenseCategory(id, name) {
    showConfirmPopup(
        'Delete Category',
        `Are you sure you want to delete "<strong>${name}</strong>"?`,
        () => {
            fetch(`api/delete_expense_category.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlertPopup('Deleted', `Category "${name}" has been deleted.`, 'success');
                        loadExpenseCategories();
                    } else {
                        showAlertPopup('Error', data.error || 'Failed to delete category', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlertPopup('Error', 'Something went wrong. Please try again.', 'error');
                });
        }
    );
}

// --- Payment Modes Management Functions ---

// Load payment modes into add form dropdowns dynamically
function loadPaymentModesDropdowns() {
    return fetch('api/get_payment_modes.php')
        .then(r => r.json())
        .then(modes => {
            const targets = ['incomePaymentMode', 'expensePaymentMode', 'salaryPaymentMode'];
            targets.forEach(id => {
                const sel = document.getElementById(id);
                if (sel) {
                    let html = '<option value="">Select Payment Mode</option>';
                    modes.forEach(mode => {
                        html += `<option value="${mode.mode_name}">${mode.mode_name}</option>`;
                    });
                    sel.innerHTML = html;
                }
            });
            return modes;
        })
        .catch(err => console.error('Error loading payment modes:', err));
}

function openPaymentModesModal() {
    document.getElementById('paymentModesModal').classList.add('show');
    loadPaymentModesForManagement();
}

function closePaymentModesModal() {
    document.getElementById('paymentModesModal').classList.remove('show');
    document.getElementById('newPaymentModeInput').value = '';
}

// Load payment modes for management
function loadPaymentModesForManagement() {
    fetch('api/get_payment_modes.php')
        .then(response => response.json())
        .then(modes => {
            displayPaymentModes(modes);
        })
        .catch(error => {
            console.error('Error fetching payment modes:', error);
            displayPaymentModes([]);
        });
}

// Display payment modes inside modal list
function displayPaymentModes(modes) {
    const container = document.getElementById('paymentModeList');
    if (!container) return;

    if (!modes || modes.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">No payment methods found.</p>';
        return;
    }

    let html = '';
    modes.forEach(mode => {
        html += `
        <div class="category-item">
            <span class="category-name">${mode.mode_name}</span>
            <button class="btn-delete-icon" onclick="deletePaymentMode(${mode.id}, '${mode.mode_name}')" title="Delete">✖</button>
        </div>`;
    });

    container.innerHTML = html;
}

// Add a new payment mode
function addPaymentMode() {
    const input = document.getElementById('newPaymentModeInput');
    const name = input.value.trim();
    if (!name) {
        showAlertPopup('Warning', 'Payment method name cannot be empty', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('mode_name', name);

    fetch('api/add_payment_mode.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                loadPaymentModesForManagement();
                loadPaymentModesDropdowns();
            } else {
                showAlertPopup('Error', data.error || 'Failed to add payment method', 'error');
            }
        })
        .catch(error => {
            console.error('Error adding payment mode:', error);
            showAlertPopup('Error', 'Failed to save payment method due to a network error.', 'error');
        });
}

// Delete a payment mode
function deletePaymentMode(id, name) {
    showConfirmPopup(
        'Delete Payment Method',
        `Are you sure you want to delete payment method "<strong>${name}</strong>"?`,
        () => {
            fetch(`api/delete_payment_mode.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlertPopup('Deleted', `Payment method "${name}" has been deleted.`, 'success');
                        loadPaymentModesForManagement();
                        loadPaymentModesDropdowns();
                    } else {
                        showAlertPopup('Error', data.error || 'Failed to delete payment method', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error deleting payment mode:', error);
                    showAlertPopup('Error', 'Something went wrong. Please try again.', 'error');
                });
        }
    );
}

// --- Salary Logs Functionality ---

// Load salary logs
function loadSalaryLogs() {
    fetch('api/salary_logs.php')
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                displaySalaryLogs(data);
                updateSalaryStats(data);
            } catch (e) {
                console.error('Server Error loading logs:', text);
                // Don't alert on load, just log to console to avoid annoying popups on page load
                document.getElementById('salary-list').innerHTML = '<p style="color:red; text-align:center;">Failed to load data. <br>Error: invalid server response.<br>Check console for details.</p>';
            }
        })
        .catch(error => console.error('Network Error:', error));
}

// Display salary logs in table
function displaySalaryLogs(logs) {
    const container = document.getElementById('salary-list');

    if (logs.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No salary records found. Add your first record!</p>';
        return;
    }

    let html = `<table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Payment Date</th>
                <th>Employee Name</th>
                <th>Role</th>
                <th>Month</th>
                <th>Mode</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;

    logs.forEach((log, index) => {
        const amount = parseFloat(log.amount);
        const statusClass = log.status === 'Paid' ? 'badge-green' : 'badge-red';

        html += `<tr>
            <td>${index + 1}</td>
            <td>${log.payment_date}</td>
            <td>${log.employee_name}</td>
            <td>${log.role || '-'}</td>
            <td>${log.month || '-'}</td>
            <td>${log.payment_mode || '-'}</td>
            <td style="font-weight: 600;">₹${amount.toFixed(2)}</td>
            <td><span class="badge ${statusClass}">${log.status || 'Paid'}</span></td>
            <td>
                <button class="btn-action btn-edit-small" onclick="viewSalaryPayslip('${log.employee_name}', '${log.month}', '${log.payment_date}', ${amount}, '${log.payment_mode || ''}')" title="View Slip" style="background:#e0f2fe; color:#0284c7;">🧾</button>
                <button class="btn-action btn-edit-small" onclick="openEditSalaryModal(${log.id}, '${log.employee_name}', '${log.role || ''}', '${log.month}', ${amount}, '${log.payment_date}', '${log.payment_mode || ''}', '${log.status || 'Paid'}')" title="Edit"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                <button class="btn-action btn-delete-small" onclick="deleteSalaryLog(${log.id})" title="Delete"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Update salary statistics
function updateSalaryStats(logs) {
    const paidLogs = logs.filter(log => !log.status || log.status === 'Paid');
    const totalPaid = paidLogs.reduce((sum, log) => sum + parseFloat(log.amount), 0);
    const employees = new Set(logs.map(log => log.employee_name));

    document.getElementById('total-salary-paid').textContent = '₹' + totalPaid.toFixed(2);
    document.getElementById('total-employees-paid').textContent = employees.size;

    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    const lastMonth = currentMonth === 0 ? 11 : currentMonth - 1;
    const lastMonthYear = currentMonth === 0 ? currentYear - 1 : currentYear;

    const thisMonthPaid = paidLogs.filter(log => {
        const d = new Date(log.payment_date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).reduce((sum, log) => sum + parseFloat(log.amount), 0);

    const lastMonthPaid = paidLogs.filter(log => {
        const d = new Date(log.payment_date);
        return d.getMonth() === lastMonth && d.getFullYear() === lastMonthYear;
    }).reduce((sum, log) => sum + parseFloat(log.amount), 0);

    const thisMonthEl = document.getElementById('salary-paid-this-month');
    if (thisMonthEl) thisMonthEl.textContent = '₹' + thisMonthPaid.toFixed(2);

    const lastMonthEl = document.getElementById('salary-paid-last-month');
    if (lastMonthEl) lastMonthEl.textContent = '₹' + lastMonthPaid.toFixed(2);
}

// Modal functions for Salary
function showAddSalaryModal() {
    document.getElementById('addSalaryModal').classList.add('show');
    document.getElementById('salaryPaymentDate').valueAsDate = new Date();
    loadEmployees();
    loadPaymentModesDropdowns();
}

function closeAddSalaryModal() {
    document.getElementById('addSalaryModal').classList.remove('show');
    document.getElementById('salaryForm').reset();
}

// Submit salary form
function submitSalary(event) {
    event.preventDefault();

    const formData = new FormData();
    const empSelect = document.getElementById('salaryEmployeeSelect');
    const empName = empSelect.options[empSelect.selectedIndex].text;

    if (empSelect.value === "") {
        alert("Please select an employee");
        return;
    }

    const month = document.getElementById('salaryMonth').value;
    const paymentDate = document.getElementById('salaryPaymentDate').value;
    const amount = document.getElementById('salaryAmount').value;
    const paymentMode = document.getElementById('salaryPaymentMode').value;

    formData.append('employee_name', empName);
    formData.append('month', month);
    formData.append('payment_date', paymentDate);
    formData.append('amount', amount);
    formData.append('payment_mode', paymentMode);

    fetch('api/add_salary_log.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    closeAddSalaryModal();
                    loadSalaryLogs();
                    // Show payslip
                    showPayslipReceipt(empName, month, paymentDate, parseFloat(amount), paymentMode);
                } else {
                    alert('Error: ' + (data.error || 'Failed to add salary record'));
                }
            } catch (e) {
                console.error('Server Error:', text);
                alert('Server Error: ' + text.substring(0, 100) + '...');
            }
        })
        .catch(error => console.error('Network Error:', error));
}

// Delete salary log
function deleteSalaryLog(id) {
    if (confirm('Are you sure you want to delete this salary record?')) {
        fetch('api/delete_salary_log.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadSalaryLogs();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete record'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete record. See console for details.');
            });
    }
}

// Show payslip receipt in a modal after salary is paid
function showPayslipReceipt(empName, month, paymentDate, amount, paymentMode) {
    const monthLabel = month ? new Date(month + '-01').toLocaleDateString('en-IN', { month: 'long', year: 'numeric' }) : '';
    const dateLabel = paymentDate ? new Date(paymentDate).toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
    const receiptNo = 'SAL-' + Date.now().toString().slice(-6);

    const html = `
        <div id="payslipPrintArea" style="font-family: 'Segoe UI', sans-serif; max-width: 520px; margin: 0 auto; border: 2px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: white; padding: 24px; text-align: center;">
                <div style="font-size: 22px; font-weight: 700; letter-spacing: 1px;">SALARY PAYMENT RECEIPT</div>
                <div style="font-size: 13px; margin-top: 4px; opacity: 0.85;">Receipt No: ${receiptNo}</div>
            </div>
            <div style="padding: 24px; background: #fff;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; color: #64748b; font-size: 13px;">Employee Name</td>
                        <td style="padding: 12px 0; font-weight: 600; text-align: right;">${empName}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; color: #64748b; font-size: 13px;">Salary Month</td>
                        <td style="padding: 12px 0; font-weight: 600; text-align: right;">${monthLabel}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; color: #64748b; font-size: 13px;">Payment Date</td>
                        <td style="padding: 12px 0; font-weight: 600; text-align: right;">${dateLabel}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 0; color: #64748b; font-size: 13px;">Payment Mode</td>
                        <td style="padding: 12px 0; font-weight: 600; text-align: right;">${paymentMode}</td>
                    </tr>
                    <tr>
                        <td style="padding: 16px 0 8px; color: #1e293b; font-size: 15px; font-weight: 600;">Net Amount Paid</td>
                        <td style="padding: 16px 0 8px; font-size: 20px; font-weight: 700; color: #10b981; text-align: right;">₹${amount.toFixed(2)}</td>
                    </tr>
                </table>
                <div style="margin-top: 20px; padding: 12px 16px; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981; font-size: 13px; color: #166534;">
                    ✅ Salary paid successfully and recorded in the system.
                </div>
            </div>
        </div>`;

    document.getElementById('payslipViewContent').innerHTML = html;
    document.getElementById('payslipViewModal').classList.add('show');
}

function closePayslipViewModal() {
    document.getElementById('payslipViewModal').classList.remove('show');
}

function printPayslipModal() {
    const content = document.getElementById('payslipPrintArea').innerHTML;
    const win = window.open('', '_blank');
    win.document.write(`<!DOCTYPE html><html><head><title>Salary Receipt</title>
    <style>body{font-family:'Segoe UI',sans-serif;margin:0;padding:20px;} @media print{body{padding:0;}}</style>
    </head><body>${content}</body></html>`);
    win.document.close();
    win.focus();
    win.print();
    win.close();
}

// Add payslip view button to salary logs table
function viewSalaryPayslip(empName, month, paymentDate, amount, paymentMode) {
    showPayslipReceipt(empName, month, paymentDate, amount, paymentMode);
}

// Window click to close modal needs update
// We'll add a new listener instead of modifying the existing one to avoid complexity
window.addEventListener('click', function (event) {
    const salaryModal = document.getElementById('addSalaryModal');
    const employeeModal = document.getElementById('employeeModal');
    const payslipModal = document.getElementById('payslipViewModal');
    if (event.target == salaryModal) {
        closeAddSalaryModal();
    }
    if (event.target == employeeModal) {
        closeEmployeeModal();
    }
    if (event.target == payslipModal) {
        closePayslipViewModal();
    }
});

// Open edit modal with data
function openEditSalaryModal(id, employee_name, role, month, amount, payment_date, payment_mode, status) {
    document.getElementById('editSalaryId').value = id;
    document.getElementById('editSalaryEmployeeName').value = employee_name;
    document.getElementById('editSalaryRole').value = role;
    document.getElementById('editSalaryMonth').value = month;
    document.getElementById('editSalaryPaymentDate').value = payment_date;
    document.getElementById('editSalaryAmount').value = amount;
    document.getElementById('editSalaryPaymentMode').value = payment_mode;
    document.getElementById('editSalaryStatus').value = status;

    document.getElementById('editSalaryModal').classList.add('show');
}

function closeEditSalaryModal() {
    document.getElementById('editSalaryModal').classList.remove('show');
}

function submitEditSalary(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('id', document.getElementById('editSalaryId').value);
    formData.append('employee_name', document.getElementById('editSalaryEmployeeName').value);
    formData.append('role', document.getElementById('editSalaryRole').value);
    formData.append('month', document.getElementById('editSalaryMonth').value);
    formData.append('payment_date', document.getElementById('editSalaryPaymentDate').value);
    formData.append('amount', document.getElementById('editSalaryAmount').value);
    formData.append('payment_mode', document.getElementById('editSalaryPaymentMode').value);
    formData.append('status', document.getElementById('editSalaryStatus').value);

    fetch('api/edit_salary_log.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeEditSalaryModal();
                loadSalaryLogs();
            } else {
                alert('Error: ' + (data.error || 'Failed to update salary record'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to update record');
        });
}

// --- Loans Functionality ---
// --- Loans Functionality ---
function loadLoans() {
    fetch('api/loans.php')
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (Array.isArray(data)) {
                    displayLoans(data);
                    updateLoanStats(data);
                } else {
                    console.error('Failed to load loans:', data);
                    displayLoans([]);
                    updateLoanStats([]);
                }
            } catch (e) {
                console.error('Loans Error:', text);
                displayLoans([]);
                updateLoanStats([]);
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            displayLoans([]);
        });
}

function displayLoans(loans) {
    const container = document.getElementById('loans-list');
    if (!container) return;
    if (loans.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No loans found. Add your first loan!</p>';
        return;
    }

    let html = `<table class="table">
        <thead>
            <tr>
                <th>Creditor</th>
                <th style="min-width:200px;">Progress</th>
                <th>Amount</th>
                <th>Interest</th>
                <th>Next Due</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;

    loans.forEach(loan => {
        const principal   = parseFloat(loan.principal_amount);
        const paid        = parseFloat(loan.paid_amount || 0);
        const outstanding = Math.max(principal - paid, 0);
        const percent     = principal > 0 ? Math.min((paid / principal) * 100, 100) : 0;
        const rate        = parseFloat(loan.interest_rate || 0);
        const isActive    = loan.status === 'Active';

        // Monthly interest due date
        let dueDateLabel = '—';
        if (isActive && rate > 0 && loan.start_date) {
            const startDay = new Date(loan.start_date).getDate();
            const today = new Date();
            let due = new Date(today.getFullYear(), today.getMonth(), startDay);
            if (due <= today) due = new Date(today.getFullYear(), today.getMonth() + 1, startDay);
            dueDateLabel = due.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        const monthlyInterest = outstanding > 0 && rate > 0 ? ((outstanding * rate) / 100 / 12) : 0;
        const statusBadge = isActive
            ? `<span style="display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:600; background:#dcfce7; color:#16a34a;">Active</span>`
            : `<span style="display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:600; background:#fee2e2; color:#dc2626;">Settled</span>`;

        const barColor = percent >= 100 ? '#10b981' : percent >= 50 ? '#f59e0b' : '#6366f1';
        const safeName = (loan.creditor_name || '').replace(/'/g, "\\'");

        // SVG icons
        const iconHistory  = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
        const iconInterest = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`;
        const iconPrincipal= `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>`;
        const iconEdit     = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`;
        const iconDelete   = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>`;

        html += `<tr>
            <td>
                <div style="font-weight:600; font-size:14px; margin-bottom:4px;">${loan.creditor_name}</div>
                ${statusBadge}
            </td>
            <td>
                <div style="display:flex; justify-content:space-between; font-size:11px; color:#64748b; margin-bottom:5px;">
                    <span style="font-weight:500;">₹${paid.toLocaleString('en-IN', {maximumFractionDigits:0})} paid</span>
                    <span style="font-weight:700; color:#1e293b;">${percent.toFixed(0)}%</span>
                </div>
                <div style="width:100%; height:10px; background:#e2e8f0; border-radius:10px; overflow:hidden;">
                    <div style="width:${percent}%; height:100%; background:${barColor}; border-radius:10px; transition:width 0.4s ease;"></div>
                </div>
                <div style="font-size:11px; color:#ef4444; font-weight:500; margin-top:4px;">₹${outstanding.toLocaleString('en-IN', {maximumFractionDigits:2})} remaining</div>
            </td>
            <td>
                <div style="font-weight:700; font-size:15px;">₹${principal.toLocaleString('en-IN', {maximumFractionDigits:2})}</div>
            </td>
            <td>
                <div style="font-weight:600;">${rate}% <span style="font-size:10px; color:#64748b; font-weight:400;">p.a.</span></div>
                ${monthlyInterest > 0 ? `<div style="font-size:11px; color:#94a3b8; margin-top:2px;">~₹${monthlyInterest.toFixed(0)}/mo</div>` : '<div style="font-size:11px; color:#94a3b8;">No interest</div>'}
            </td>
            <td style="font-size:12px; color:${isActive && rate > 0 ? '#f59e0b' : '#94a3b8'}; font-weight:600;">
                ${dueDateLabel}
            </td>
            <td>
                <div style="display:flex; gap:5px; align-items:center;">
                    <button class="btn-action btn-edit-small" onclick="openHistoryModal(${loan.id})" title="View History" style="background:#e0f2fe; color:#0284c7; width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px;">${iconHistory}</button>
                    <button class="btn-action btn-edit-small" onclick="openPayInterestModal(${loan.id}, ${outstanding}, ${rate}, 'Annual', '${loan.last_interest_payment_date || ''}')" title="Pay Interest" style="background:#fef3c7; color:#d97706; width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px;">${iconInterest}</button>
                    <button class="btn-action btn-edit-small" onclick="openRepayModal(${loan.id}, ${outstanding})" title="Pay Principal" style="background:#dcfce7; color:#16a34a; width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px;">${iconPrincipal}</button>
                    <button class="btn-action btn-edit-small" onclick="openEditLoanModal(${loan.id}, '${safeName}', ${principal}, ${rate}, 'Annual', '${loan.start_date}', '${loan.status}', '')" title="Edit" style="background:#f1f5f9; color:#475569; width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px;">${iconEdit}</button>
                    <button class="btn-action btn-delete-small" onclick="deleteLoan(${loan.id})" title="Delete" style="width:30px; height:30px; padding:0; display:inline-flex; align-items:center; justify-content:center; border-radius:6px;">${iconDelete}</button>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function updateLoanStats(loans) {
    const totalTaken = loans.reduce((sum, l) => sum + parseFloat(l.principal_amount), 0);
    const totalPaidBack = loans.reduce((sum, l) => sum + parseFloat(l.paid_amount || 0), 0);
    const activeLoans = loans.filter(l => l.status === 'Active');
    const activeAmount = activeLoans.reduce((sum, l) => sum + (parseFloat(l.principal_amount) - parseFloat(l.paid_amount || 0)), 0);

    const el = id => document.getElementById(id);
    if (el('total-loan-taken'))    el('total-loan-taken').textContent    = '₹' + totalTaken.toFixed(2);
    if (el('active-loan-amount'))  el('active-loan-amount').textContent  = '₹' + activeAmount.toFixed(2);
    if (el('total-loan-paid-back'))el('total-loan-paid-back').textContent= '₹' + totalPaidBack.toFixed(2);

    // Total interest paid — from API
    fetch('api/loan_stats.php')
        .then(r => r.json())
        .then(data => {
            if (data.success && el('total-interest-paid')) {
                el('total-interest-paid').textContent = '₹' + parseFloat(data.total_interest_paid).toFixed(2);
            }
        })
        .catch(e => console.error('Stats Error:', e));
}

function updateLoanSourceLabel() {
    const type = document.getElementById('loanSourceType').value;
    const label = document.getElementById('loanCreditorLabel');
    if (label) label.innerHTML = (type === 'Bank' ? 'Bank Name' : 'Person Name') + ' <span class="required">*</span>';
}

function showAddLoanModal() {
    document.getElementById('addLoanModal').classList.add('show');
    document.getElementById('loanStartDate').valueAsDate = new Date();
    // Load payment modes from settings
    fetch('api/get_payment_modes.php')
        .then(r => r.json())
        .then(modes => {
            const sel = document.getElementById('loanPaymentMode');
            let html = '<option value="">Select Payment Mode</option>';
            modes.forEach(m => { html += `<option value="${m.mode_name}">${m.mode_name}</option>`; });
            sel.innerHTML = html;
        })
        .catch(e => console.error('Error loading payment modes:', e));
}

function closeAddLoanModal() {
    document.getElementById('addLoanModal').classList.remove('show');
    document.getElementById('loanForm').reset();
}

function submitLoan(event) {
    event.preventDefault();

    const sourceType = document.getElementById('loanSourceType').value;
    const creditorName = document.getElementById('loanCreditor').value.trim();
    const fullName = sourceType ? `${sourceType}: ${creditorName}` : creditorName;

    const formData = new FormData();
    formData.append('creditor_name', fullName);
    formData.append('principal_amount', document.getElementById('loanAmount').value);
    formData.append('charges', document.getElementById('loanCharges').value || 0);
    formData.append('interest_rate', document.getElementById('loanInterest').value || 0);
    formData.append('start_date', document.getElementById('loanStartDate').value);
    formData.append('payment_mode', document.getElementById('loanPaymentMode').value);
    formData.append('description', document.getElementById('loanDescription').value);

    fetch('api/add_loan.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeAddLoanModal();
                loadLoans();
            } else {
                alert('Error: ' + (data.error || 'Failed to add loan'));
            }
        })
        .catch(error => console.error('Error:', error));
}

function deleteLoan(id) {
    if (confirm('Are you sure you want to delete this loan record?')) {
        fetch('api/delete_loan.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadLoans();
                } else {
                    alert('Error: ' + data.error);
                }
            });
    }
}

// Edit Loan Functions
function openEditLoanModal(loanId, creditor, amount, rate, type, date, status, description) {
    document.getElementById('editLoanId').value = loanId;
    document.getElementById('editLoanCreditor').value = creditor;
    document.getElementById('editLoanAmount').value = amount;
    document.getElementById('editLoanInterest').value = rate;
    document.getElementById('editLoanStartDate').value = date;
    document.getElementById('editLoanStatus').value = status;
    document.getElementById('editLoanDescription').value = description || '';

    // Set radio
    const radios = document.getElementsByName('editInterestType');
    for (let r of radios) {
        if (r.value === type) r.checked = true;
    }

    document.getElementById('editLoanModal').classList.add('show');
}

function closeEditLoanModal() {
    document.getElementById('editLoanModal').classList.remove('show');
}

function submitEditLoan(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('id', document.getElementById('editLoanId').value);
    formData.append('creditor_name', document.getElementById('editLoanCreditor').value);
    formData.append('principal_amount', document.getElementById('editLoanAmount').value);
    formData.append('interest_rate', document.getElementById('editLoanInterest').value);

    // Check if radio exists before getting value, default to Monthly if not found (though it should be there)
    const interestTypeEl = document.querySelector('input[name="editInterestType"]:checked');
    const interestType = interestTypeEl ? interestTypeEl.value : 'Monthly';
    formData.append('interest_type', interestType);

    formData.append('start_date', document.getElementById('editLoanStartDate').value);
    formData.append('status', document.getElementById('editLoanStatus').value);
    formData.append('description', document.getElementById('editLoanDescription').value);

    fetch('api/edit_loan.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeEditLoanModal();
                loadLoans();
                if (typeof loadIncomes === 'function') loadIncomes();
                if (typeof loadReports === 'function') loadReports();
            } else {
                alert('Error: ' + (data.error || 'Failed to update loan'));
            }
        })
        .catch(error => console.error('Error:', error));
}

// Interest Payment Modal
function openPayInterestModal(loanId, principal, rate, type, lastPaymentDate) {
    document.getElementById('payInterestLoanId').value = loanId;

    if (lastPaymentDate) {
        const lastDate = new Date(lastPaymentDate);
        // Add 1 month
        // Handle edge cases like Jan 31 -> Feb 28/29? Date object handles overflow by moving to next month.
        // But for interest, usually we want the same day or end of month.
        // Simple increment:
        lastDate.setMonth(lastDate.getMonth() + 1);
        document.getElementById('payInterestDate').valueAsDate = lastDate;
    } else {
        document.getElementById('payInterestDate').valueAsDate = new Date();
    }

    // Calculate Amount
    let monthlyRate = parseFloat(rate);
    if (type === 'Annual') {
        monthlyRate = monthlyRate / 12;
    }
    const amount = (parseFloat(principal) * monthlyRate) / 100;

    document.getElementById('payInterestAmountDisplay').value = amount.toFixed(2);
    // document.getElementById('payInterestAmountValue').value = amount.toFixed(2); // No longer needed

    document.getElementById('payInterestModal').classList.add('show');
}

function closePayInterestModal() {
    document.getElementById('payInterestModal').classList.remove('show');
}

function submitInterestPayment(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('loan_id', document.getElementById('payInterestLoanId').value);
    formData.append('payment_date', document.getElementById('payInterestDate').value);
    formData.append('custom_amount', document.getElementById('payInterestAmountDisplay').value);

    fetch('api/pay_loan_interest.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closePayInterestModal();
                loadLoans();
                // Refresh expenses
                if (typeof loadExpenses === 'function') loadExpenses();
                if (typeof loadReports === 'function') loadReports();
            } else {
                alert('Error: ' + (data.error || 'Failed to record payment'));
            }
        })
        .catch(error => console.error('Error:', error));
}

// History Functions
function openHistoryModal(loanId) {
    const tbody = document.querySelector('#loanHistoryTable tbody');
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">Loading...</td></tr>';
    document.getElementById('loanHistoryModal').classList.add('show');

    fetch('api/get_loan_history.php?loan_id=' + loanId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.history.length > 0) {
                let html = '';
                data.history.forEach(item => {
                    const isPrincipal = item.type === 'repayment' || item.type === 'principal';
                    const typeLabel = isPrincipal
                        ? `<span class="badge badge-green" style="font-size:10px;">Principal</span>`
                        : `<span class="badge" style="background:#fef3c7; color:#d97706; font-size:10px;">Interest</span>`;
                    html += `<tr>
                        <td>${item.date}</td>
                        <td>${typeLabel}</td>
                        <td style="font-weight:600; color:#ef4444;">₹${parseFloat(item.amount).toFixed(2)}</td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#64748b;">No payment history found.</td></tr>';
            }
        })
        .catch(error => {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">Failed to load history.</td></tr>';
        });
}

function closeHistoryModal() {
    document.getElementById('loanHistoryModal').classList.remove('show');
}

// Repay Principal Modal
function openRepayModal(loanId, outstanding) {
    document.getElementById('repayLoanId').value = loanId;
    document.getElementById('repayOutstandingDisplay').textContent = '₹' + outstanding.toFixed(2);
    document.getElementById('repayAmount').value = '';
    document.getElementById('repayDate').valueAsDate = new Date();
    document.getElementById('repayLoanModal').classList.add('show');
}

function closeRepayModal() {
    document.getElementById('repayLoanModal').classList.remove('show');
}

function submitRepayment(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('loan_id', document.getElementById('repayLoanId').value);
    formData.append('amount', document.getElementById('repayAmount').value);
    formData.append('payment_date', document.getElementById('repayDate').value);

    fetch('api/repay_loan.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeRepayModal();
                loadLoans();
                // Refresh expenses and stats
                if (typeof loadExpenses === 'function') loadExpenses();
                if (typeof loadReports === 'function') loadReports();

                if (data.new_status === 'Closed') {
                    alert('Loan Repaid Fully! Status updated to Closed.');
                }
            } else {
                alert('Error: ' + (data.error || 'Failed to record repayment'));
            }
        })
        .catch(error => console.error('Error:', error));
}

// Auto-categorization logic
function setupAutoCategorization() {
    const descriptionInput = document.getElementById('incomeDescription');
    const categorySelect = document.getElementById('incomeCategorySelect');

    if (descriptionInput && categorySelect) {
        descriptionInput.addEventListener('input', function () {
            const value = this.value.toLowerCase();
            if (value.includes('salary')) {
                // Try to find an option with value 'Salary' (case-insensitive check might be needed if values vary, but usually it's Capitalized)
                // We'll try exact match 'Salary' first, then look for case-insensitive match
                let options = Array.from(categorySelect.options);
                let salaryOption = options.find(opt => opt.value === 'Salary' || opt.value.toLowerCase() === 'salary');

                if (salaryOption) {
                    categorySelect.value = salaryOption.value;
                }
            }
        });
    }
}

// --- Employee Management Functions ---

function openEmployeeModal() {
    document.getElementById('employeeModal').classList.add('show');
    loadEmployees();
}

function closeEmployeeModal() {
    document.getElementById('employeeModal').classList.remove('show');
}

function loadEmployees() {
    fetch('api/get_employees.php')
        .then(response => response.text())
        .then(text => {
            try {
                const employees = JSON.parse(text);
                displayEmployees(employees);
                updateSalaryEmployeeDropdown(employees);
            } catch (e) {
                console.error('Employees Error:', text);
                displayEmployees([]);
                updateSalaryEmployeeDropdown([]);
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            displayEmployees([]);
            updateSalaryEmployeeDropdown([]);
        });
}

function displayEmployees(employees) {
    const container = document.getElementById('employeeList');
    if (employees.length === 0) {
        container.innerHTML = '<p style="text-align:center; padding:20px; color:#64748b;">No employees found.</p>';
        return;
    }

    let html = '';
    employees.forEach(emp => {
        html += `
        <div class="category-item">
            <div>
                <span class="category-name">${emp.name}</span>
                <small style="display:block; color:#64748b; font-size:11px;">${emp.role || 'No Role'}</small>
            </div>
            <button class="btn-delete-icon" onclick="deleteEmployee(${emp.id}, '${emp.name}')" title="Delete">✖</button>
        </div>`;
    });
    container.innerHTML = html;
}

function updateSalaryEmployeeDropdown(employees) {
    const select = document.getElementById('salaryEmployeeSelect');
    // Save current selection if any
    const currentVal = select.value;

    let html = '<option value="">Select Employee</option>';
    employees.forEach(emp => {
        html += `<option value="${emp.id}" data-role="${emp.role || ''}">${emp.name}</option>`;
    });

    select.innerHTML = html;
    // Restore value if it still exists (might fail if value was Name and we switched to ID, but wait, I am using ID in value)
    // Previously it was an input, so no previous value to restore from ID perspective.
    // But if we reload while modal is open, we might want to keep it.
    // However, since we use ID now, and stored logs use Name...
    // The dropdown is for NEW logs. Existing logs use text display.
    if (currentVal) select.value = currentVal;
}

function addEmployee() {
    const nameInput = document.getElementById('newEmployeeNameInput');
    const name = nameInput.value.trim();

    if (!name) {
        alert("Please enter employee name");
        return;
    }

    const formData = new FormData();
    formData.append('name', name);

    fetch('api/add_employee.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                nameInput.value = '';
                loadEmployees();
            } else {
                alert(data.error || 'Failed to add employee');
            }
        })
        .catch(error => console.error('Error:', error));
}

function deleteEmployee(id, name) {
    if (!confirm(`Are you sure you want to delete employee "${name}"?`)) {
        return;
    }

    fetch(`api/delete_employee.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadEmployees();
            } else {
                alert(data.error || 'Failed to delete employee');
            }
        })
        .catch(error => console.error('Error:', error));
}

// Add Report Function
// Add Report Function
function addReport() {
    document.getElementById('addReportModal').classList.add('show');

    const monthInput = document.getElementById('reportMonth');
    // Default to current month
    const now = new Date();
    const monthStr = now.toISOString().slice(0, 7); // YYYY-MM
    monthInput.value = monthStr;

    // Trigger check immediately
    fetchPreviousBalance(monthStr);

    // Add change listener
    monthInput.onchange = function () {
        fetchPreviousBalance(this.value);
    };
}

function fetchPreviousBalance(monthStr) {
    if (!monthStr) return;

    fetch(`api/get_previous_balance.php?month=${monthStr}`)
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    document.getElementById('reportOpeningBalance').value = parseFloat(data.opening_balance).toFixed(2);
                } else {
                    document.getElementById('reportOpeningBalance').value = "0.00";
                    if (data.message) console.log("API Info:", data.message);
                }
            } catch (e) {
                console.error("JSON Parse Error:", e);
                console.error("Raw Response:", text);
            }
        })
        .catch(e => console.error("Network Error:", e));
}

function closeAddReportModal() {
    document.getElementById('addReportModal').classList.remove('show');
    document.getElementById('addReportForm').reset();
}

function submitAddReport(event) {
    event.preventDefault();

    const monthInput = document.getElementById('reportMonth').value; // YYYY-MM
    const openingBalance = parseFloat(document.getElementById('reportOpeningBalance').value) || 0;
    const paymentMode = document.getElementById('reportPaymentMode').value;

    // Convert YYYY-MM to MMM-YYYY (e.g., JAN-2026)
    const [year, month] = monthInput.split('-');
    const dateObj = new Date(year, month - 1);
    const monthName = dateObj.toLocaleString('default', { month: 'short' }).toUpperCase();
    const formattedMonth = `${monthName}-${year}`;

    const formData = new FormData();
    formData.append('month', formattedMonth);
    formData.append('opening_balance', openingBalance);
    formData.append('payment_mode', paymentMode);
    formData.append('income', 0);
    formData.append('expenses', 0);
    // Assuming initial closing balance = opening balance
    formData.append('closing_balance', openingBalance);

    fetch('api/add_report.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    alert('Report created successfully');
                    closeAddReportModal();
                    loadReports();
                } else {
                    alert('Error: ' + (data.error || 'Failed to create report'));
                }
            } catch (e) {
                console.error("JSON Parse Error:", e);
                console.error("Raw Response:", text);
                alert("Server Error. Check console for details.");
            }
        })
        .catch(error => console.error('Error:', error));
}

// Logout Function
function logout() {
    if (confirm('Are you sure you want to sign out?')) {
        fetch('api/logout.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../dashboard.php';
                } else {
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(error => {
                console.error('Logout Error:', error);
                // Fallback redirect even if fetch fails (connection issue etc)
                window.location.href = '../dashboard.php';
            });
    }
}

// ==========================================
// 1. Transactions Ledger Module
// ==========================================
let currentLedgerTab = 'income';
let allIncomesData = [];
let allExpensesData = [];

function switchLedgerTab(tab) {
    currentLedgerTab = tab;
    
    const incomeTabBtn = document.getElementById('txn-tab-income');
    const expenseTabBtn = document.getElementById('txn-tab-expense');
    
    if (tab === 'income') {
        if (incomeTabBtn) {
            incomeTabBtn.classList.add('active');
            incomeTabBtn.style.background = '#ffffff';
            incomeTabBtn.style.color = '#1e293b';
        }
        if (expenseTabBtn) {
            expenseTabBtn.classList.remove('active');
            expenseTabBtn.style.background = 'transparent';
            expenseTabBtn.style.color = '#64748b';
        }
        
        document.getElementById('btn-add-income').style.display = 'inline-block';
        document.getElementById('btn-add-expense').style.display = 'none';
        
        document.getElementById('btn-manage-income-cats').style.display = 'inline-block';
        document.getElementById('btn-manage-expense-cats').style.display = 'none';
        
        document.getElementById('txn-total-label').textContent = 'Total Incomes Value';
        document.getElementById('txn-count-label').textContent = 'Incomes Count';
    } else {
        if (expenseTabBtn) {
            expenseTabBtn.classList.add('active');
            expenseTabBtn.style.background = '#ffffff';
            expenseTabBtn.style.color = '#1e293b';
        }
        if (incomeTabBtn) {
            incomeTabBtn.classList.remove('active');
            incomeTabBtn.style.background = 'transparent';
            incomeTabBtn.style.color = '#64748b';
        }
        
        document.getElementById('btn-add-income').style.display = 'none';
        document.getElementById('btn-add-expense').style.display = 'inline-block';
        
        document.getElementById('btn-manage-income-cats').style.display = 'none';
        document.getElementById('btn-manage-expense-cats').style.display = 'inline-block';
        
        document.getElementById('txn-total-label').textContent = 'Total Expenses Value';
        document.getElementById('txn-count-label').textContent = 'Expenses Count';
    }
    
    loadLedgerData();
}

function loadTransactions() {
    loadLedgerData();
}

function loadLedgerData() {
    const url = currentLedgerTab === 'income' ? 'api/incomes.php' : 'api/expenses.php';
    const container = document.getElementById('txn-table-container');
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            if (currentLedgerTab === 'income') {
                allIncomesData = data;
            } else {
                allExpensesData = data;
            }
            applyTxnFilters();
        })
        .catch(err => {
            console.error('Error loading ledger data:', err);
            if (container) {
                container.innerHTML = '<p style="padding: 40px; text-align: center; color: #ef4444;">Failed to load transactions.</p>';
            }
        });
}

let currentPeriodFilter = 'this-month';

function selectPeriod(event, period) {
    if (event) event.preventDefault();
    currentPeriodFilter = period;
    
    document.querySelectorAll('.period-filter-link').forEach(link => {
        if (link.getAttribute('data-period') === period) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
    
    const specDateInput = document.getElementById('txn-specific-date');
    const rangeContainer = document.getElementById('txn-date-range-container');
    
    if (period === 'specific-date') {
        if (specDateInput) specDateInput.style.display = 'inline-block';
        if (rangeContainer) rangeContainer.style.display = 'none';
    } else if (period === 'date-range') {
        if (specDateInput) specDateInput.style.display = 'none';
        if (rangeContainer) rangeContainer.style.display = 'flex';
    } else {
        if (specDateInput) specDateInput.style.display = 'none';
        if (rangeContainer) rangeContainer.style.display = 'none';
    }
    
    applyTxnFilters();
}

function onTxnFilterChange() {
    applyTxnFilters();
}

function parseLocalDate(dateStr) {
    if (!dateStr) return null;
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    }
    return new Date(dateStr);
}

function applyTxnFilters() {
    const query = (document.getElementById('txn-search')?.value || '').toLowerCase();
    const timeFilter = currentPeriodFilter;
    const dataList = currentLedgerTab === 'income' ? allIncomesData : allExpensesData;
    
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    let prevMonth = currentMonth - 1;
    let prevYear = currentYear;
    if (prevMonth < 0) {
        prevMonth = 11;
        prevYear = currentYear - 1;
    }

    const filtered = dataList.filter(item => {
        const matchesQuery = !query || 
            (item.description || '').toLowerCase().includes(query) ||
            (item.category || '').toLowerCase().includes(query) ||
            (item.payment_mode || '').toLowerCase().includes(query) ||
            String(item.amount).includes(query) ||
            (item.date || '').includes(query);
            
        if (!matchesQuery) return false;
        
        if (timeFilter === 'total') return true;
        
        const itemDate = parseLocalDate(item.date);
        if (!itemDate) return false;
        
        if (timeFilter === 'this-month') {
            return itemDate.getMonth() === currentMonth && itemDate.getFullYear() === currentYear;
        }
        if (timeFilter === 'last-month') {
            return itemDate.getMonth() === prevMonth && itemDate.getFullYear() === prevYear;
        }
        if (timeFilter === 'this-year') {
            return itemDate.getFullYear() === currentYear;
        }
        if (timeFilter === 'specific-date') {
            const specDateVal = document.getElementById('txn-specific-date')?.value;
            if (!specDateVal) return true;
            return item.date === specDateVal;
        }
        if (timeFilter === 'date-range') {
            const startVal = document.getElementById('txn-start-date')?.value;
            const endVal = document.getElementById('txn-end-date')?.value;
            if (!startVal && !endVal) return true;
            if (startVal && item.date < startVal) return false;
            if (endVal && item.date > endVal) return false;
            return true;
        }
        
        return true;
    });

    // Sort transactions date-wise descending (newest first)
    filtered.sort((a, b) => {
        const dateA = new Date(a.date);
        const dateB = new Date(b.date);
        if (dateB - dateA !== 0) {
            return dateB - dateA;
        }
        return b.id - a.id;
    });

    displayFilteredTxns(filtered);
}

function displayFilteredTxns(list) {
    const container = document.getElementById('txn-table-container');
    if (!container) return;

    let totalSum = 0;
    list.forEach(item => {
        totalSum += parseFloat(item.amount) || 0;
    });

    document.getElementById('txn-total-sum').textContent = '₹' + totalSum.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('txn-total-count').textContent = list.length;

    if (list.length === 0) {
        container.innerHTML = `<p style="padding: 40px; text-align: center; color: #64748b;">No ${currentLedgerTab}s found matching filters.</p>`;
        return;
    }

    let html = `<table>
        <thead>
            <tr>
                <th>S.No</th>
                <th>Date</th>
                <th>Description</th>
                <th>Category</th>
                <th>Payment Mode</th>
                <th>Attachment</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;

    list.forEach((item, index) => {
        const isIncome = currentLedgerTab === 'income';
        const color = isIncome ? '#10b981' : '#ef4444';
        const deleteFunc = isIncome ? `deleteIncome(${item.id})` : `deleteExpense(${item.id})`;
        const editFunc = isIncome ? `editIncome(${item.id})` : `editExpense(${item.id})`;
        
        const attachmentHtml = item.attachment 
            ? `<a href="${item.attachment}" target="_blank" class="attachment-badge show-attachment-link" style="color: #6366f1; text-decoration: underline; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block; vertical-align:middle;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg> View
               </a>`
            : `<span style="color: var(--text-muted); font-size: 12px;">Not Added</span>`;

        html += `<tr>
            <td>${index + 1}</td>
            <td>${new Date(item.date).toLocaleDateString('en-IN', {day: '2-digit', month: 'short', year: 'numeric'})}</td>
            <td>${item.description || '-'}</td>
            <td><span class="category-badge">${item.category || 'Other'}</span></td>
            <td>${item.payment_mode || 'Cash'}</td>
            <td>${attachmentHtml}</td>
            <td style="font-weight: 700; color: ${color}">₹${parseFloat(item.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
            <td>
                <button class="btn-action btn-edit" onclick="${editFunc}" title="Edit"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                <button class="btn-action btn-delete-small" onclick="${deleteFunc}" title="Delete"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// ==========================================
// 2. Clients Directory Module
// ==========================================
function loadClients() {
    fetch('api/get_clients.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            const tbody = document.getElementById('clients-list-container');
            if (!tbody) return;
            
            let html = `<table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GST Number</th>
                        <th>Invoices</th>
                        <th>Last Invoice Date</th>
                    </tr>
                </thead>
                <tbody>`;
            
            if (data.length === 0) {
                html += '<tr><td colspan="7" class="text-center" style="color: var(--text-muted); padding: 30px;">No clients available.</td></tr>';
            } else {
                data.forEach(client => {
                    const lastDate = client.lastInvoiceDate 
                        ? new Date(client.lastInvoiceDate).toLocaleDateString('en-IN', {day: '2-digit', month: 'short', year: 'numeric'})
                        : 'No Invoices';
                        
                    html += `
                        <tr>
                            <td><strong>#${client.id}</strong></td>
                            <td>${client.name}</td>
                            <td>${client.phone}</td>
                            <td>${client.email}</td>
                            <td><span style="font-family: monospace; font-size: 12px; color: var(--text-muted);">${client.gstNumber}</span></td>
                            <td><span class="status-badge" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.3); padding: 4px 8px; border-radius: 4px; font-weight: 600;">${client.invoiceCount} Invoices</span></td>
                            <td>${lastDate}</td>
                        </tr>
                    `;
                });
            }
            html += '</tbody></table>';
            tbody.innerHTML = html;
        })
        .catch(err => console.error('Error loading clients:', err));
}

// ==========================================
// 3. Corporate Quotations Module
// ==========================================
let allQuotations = [];

function loadQuotations() {
    fetch('api/get_quotations.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            allQuotations = data;
            displayQuotations(allQuotations);
        })
        .catch(err => console.error('Error loading quotations:', err));
}

function displayQuotations(quotes) {
    const tbody = document.getElementById('quotations-list-container');
    if (!tbody) return;
    
    let html = `<table>
        <thead>
            <tr>
                <th>Quotation No</th>
                <th>Date</th>
                <th>Prospect</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;
    
    if (quotes.length === 0) {
        html += '<tr><td colspan="6" class="text-center" style="color: var(--text-muted); padding: 30px;">No quotations found.</td></tr>';
    } else {
        quotes.forEach(q => {
            const total = parseFloat(q.totalAmount);
            const dateStr = new Date(q.date).toLocaleDateString('en-IN', {day: '2-digit', month: 'short', year: 'numeric'});
            
            html += `
                <tr>
                    <td><strong style="color: #38bdf8;">${q.quotationNo}</strong></td>
                    <td>${dateStr}</td>
                    <td>${q.clientName}<br><span style="font-size: 11px; color: var(--text-muted);">${q.phone}</span></td>
                    <td style="font-weight: 700; color: var(--text-main);">₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                    <td>
                        <span class="status-badge" style="background: rgba(14, 165, 233, 0.08); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.15); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                            ${q.status}
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <button class="btn-action btn-edit" onclick="printQuotation(${q.id})" title="Print Quotation" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.15); color: #10b981; padding: 6px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg></button>
                            <button class="btn-action btn-edit" onclick="openQuotationModal('${q.quotationNo}')" title="Edit Quotation" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 6px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                            <button class="btn-action btn-delete-small" onclick="deleteQuotation(${q.id})" title="Delete Quotation" style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.15); color: #ef4444; padding: 6px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    html += '</tbody></table>';
    tbody.innerHTML = html;
}

function filterQuotations() {
    const query = (document.getElementById('quotation-search')?.value || '').toLowerCase();
    const filtered = allQuotations.filter(q => {
        return q.quotationNo.toLowerCase().includes(query) || 
               q.clientName.toLowerCase().includes(query) ||
               q.phone.toLowerCase().includes(query);
    });
    displayQuotations(filtered);
}

// Old quotation modal functions removed - now using js/quotation_functions.js

function deleteQuotation(id) {
    if (confirm('Are you sure you want to delete this quotation permanently?')) {
        fetch('api/delete_quotation.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    loadQuotations();
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete quotation'));
                }
            })
            .catch(err => console.error('Error deleting quotation:', err));
    }
}

function printQuotation(id) {
    window.open('api/print_quotation.php?id=' + id, '_blank');
}

// ==========================================
// 4. Audit Trail Logger Module
// ==========================================
let allAuditLogs = [];

function loadAuditLogs() {
    fetch('api/get_audit_logs.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            allAuditLogs = data;
            displayAuditLogs(allAuditLogs);
        })
        .catch(err => console.error('Error loading audit logs:', err));
}

function displayAuditLogs(logs) {
    const tbody = document.getElementById('audit-logs-container');
    if (!tbody) return;
    
    let html = '';
    logs.forEach(log => {
        const timeStr = new Date(log.created_at).toLocaleString('en-IN', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
        
        let actionBadgeColor = 'rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3)';
        if (log.action.toLowerCase().includes('delete') || log.action.toLowerCase().includes('remove')) {
            actionBadgeColor = 'rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3)';
        } else if (log.action.toLowerCase().includes('add') || log.action.toLowerCase().includes('insert') || log.action.toLowerCase().includes('create')) {
            actionBadgeColor = 'rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3)';
        }
        
        html += `
            <tr>
                <td style="font-family: monospace; font-size: 12px; color: var(--text-muted);">${timeStr}</td>
                <td><strong>${log.username || 'System'}</strong></td>
                <td><span class="status-badge" style="background: ${actionBadgeColor}; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;">${log.action}</span></td>
                <td><span style="font-family: monospace; font-size: 11px; color: var(--text-muted);">${log.table_name}</span></td>
                <td><strong>#${log.record_id || '-'}</strong></td>
                <td style="font-size: 12px; color: #fff;">${log.details || '-'}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html || '<tr><td colspan="6" class="text-center" style="color: var(--text-muted); padding: 30px;">No audit records available.</td></tr>';
}

// ==========================================
// 5. Global Settings & 2FA Control
// ==========================================
function loadSettings() {
    fetch('api/get_settings.php')
        .then(res => res.json())
        .then(settings => {
            if (settings.error) {
                console.error(settings.error);
                return;
            }
            
            if (document.getElementById('setCompanyName')) {
                document.getElementById('setCompanyName').value = settings.company_name || '';
                document.getElementById('setCompanyGst').value = settings.company_gst || '';
                document.getElementById('setCompanyPhone').value = settings.company_phone || '';
                document.getElementById('setCompanyEmail').value = settings.company_email || '';
                document.getElementById('setCompanyAddress').value = settings.company_address || '';
                
                // 2FA status
                const is2FA = settings.enable_2fa === 'true' || settings.enable_2fa === '1';
                document.getElementById('set2FA').checked = is2FA;
            }
        })
        .catch(err => console.error('Error loading settings:', err));
}

function saveGlobalSettings(event) {
    event.preventDefault();
    
    const payload = {
        company_name: document.getElementById('setCompanyName').value,
        company_gst: document.getElementById('setCompanyGst').value,
        company_phone: document.getElementById('setCompanyPhone').value,
        company_email: document.getElementById('setCompanyEmail').value,
        company_address: document.getElementById('setCompanyAddress').value,
        enable_2fa: document.getElementById('set2FA').checked ? 'true' : 'false'
    };
    
    fetch('api/save_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Global Configurations saved successfully!');
            loadSettings();
        } else {
            alert('Error: ' + (data.error || 'Failed to save configurations'));
        }
    })
    .catch(err => console.error('Error saving settings:', err));
}


// Invoice functions moved to invoice_functions.js
