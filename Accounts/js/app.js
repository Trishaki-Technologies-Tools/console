// Check if running from file protocol
if (window.location.protocol === 'file:') {
    alert('⚠️ You are opening this file directly! \n\nDynamic features like Database and APIs will NOT work.\nPlease open this project through your local server (XAMPP/WAMP) at:\nhttp://localhost/Accounts/');
}

// Navigation handling
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function (e) {
        e.preventDefault();

        // Remove active class from all items
        document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));

        // Add active class to clicked item
        this.classList.add('active');

        // Get page name
        const page = this.getAttribute('data-page');

        // Hide all pages
        document.querySelectorAll('.page-content').forEach(content => content.classList.add('hidden'));

        // Show selected page
        document.getElementById(page + '-page').classList.remove('hidden');

        // Update page title
        document.getElementById('page-title').textContent = this.querySelector('span:last-child').textContent;
    });
});

// Load dashboard data on page load
window.addEventListener('DOMContentLoaded', function () {
    loadDashboardData();
    loadIncomes();
    loadExpenses();
    loadReports();
    loadSalaryLogs();
    loadLoans();
    setupAutoCategorization();
    
    // Set up continuous badge removal
    setInterval(removeBadgeElements, 1000); // Check every second
    
    // Set up mutation observer to catch dynamically added badges
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                removeBadgeElements();
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

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
function loadDashboardData() {
    fetch('api/dashboard.php')
        .then(response => response.text())
        .then(text => {
            try {
                const data = JSON.parse(text);
                document.getElementById('total-income').textContent = '₹' + data.total_income;
                document.getElementById('total-expenses').textContent = '₹' + data.total_expenses;
                document.getElementById('balance').textContent = '₹' + data.balance;
                document.getElementById('hdfc-balance').textContent = '₹' + data.hdfc_balance;
                document.getElementById('cash-balance').textContent = '₹' + data.cash_balance;
                document.getElementById('this-month-expense').textContent = '₹' + data.this_month_expense;
                document.getElementById('this-month-profit').textContent = '₹' + data.this_month_profit;
                document.getElementById('overall-profit').textContent = '₹' + data.balance;
            } catch (e) {
                console.error('Dashboard Error:', text);
                // Set default values on error
                document.getElementById('total-income').textContent = '₹0';
                document.getElementById('total-expenses').textContent = '₹0';
                document.getElementById('balance').textContent = '₹0';
                document.getElementById('hdfc-balance').textContent = '₹0';
                document.getElementById('cash-balance').textContent = '₹0';
                document.getElementById('this-month-expense').textContent = '₹0';
                document.getElementById('this-month-profit').textContent = '₹0';
                document.getElementById('overall-profit').textContent = '₹0';
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
        html += `<tr>
            <td>${index + 1}</td>
            <td>${income.date}</td>
            <td>${income.description}</td>
            <td><span class="category-badge">${income.category || 'Other'}</span></td>
            <td>${income.payment_mode || 'Cash'}</td>
            <td style="font-weight: 600; color: #10b981;">₹${parseFloat(income.amount).toFixed(2)}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editIncome(${income.id})" title="Edit">✏️</button>
                <button class="btn-action btn-delete-small" onclick="deleteIncome(${income.id})" title="Delete">🗑️</button>
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
        html += `<tr>
            <td>${index + 1}</td>
            <td>${expense.date}</td>
            <td>${expense.description}</td>
            <td><span class="category-badge">${expense.category || 'Other'}</span></td>
            <td>${expense.payment_mode || 'Cash'}</td>
            <td style="font-weight: 600; color: #ef4444;">₹${parseFloat(expense.amount).toFixed(2)}</td>
            <td>
                <button class="btn-action btn-edit" onclick="editExpense(${expense.id})" title="Edit">✏️</button>
                <button class="btn-action btn-delete-small" onclick="deleteExpense(${expense.id})" title="Delete">🗑️</button>
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
            } else {
                alert('Error: ' + (data.error || 'Failed to add income'));
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
            }
        })
        .catch(error => console.error('Error:', error));
}

// Edit income
function editIncome(id) {
    // For now, just show an alert. You can implement a modal later
    alert('Edit functionality - Income ID: ' + id);
}

// Delete income
function deleteIncome(id) {
    if (confirm('Are you sure you want to delete this income?')) {
        fetch('api/delete_income.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadIncomes();
                    loadDashboardData();
                }
            })
            .catch(error => console.error('Error:', error));
    }
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
            } else {
                alert('Error: ' + (data.error || 'Failed to add expense'));
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
            }
        })
        .catch(error => console.error('Error:', error));
}

// Edit expense
function editExpense(id) {
    alert('Edit functionality - Expense ID: ' + id);
}

// Delete expense
function deleteExpense(id) {
    if (confirm('Are you sure you want to delete this expense?')) {
        fetch('api/delete_expense.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadExpenses();
                    loadDashboardData();
                }
            })
            .catch(error => console.error('Error:', error));
    }
}


// Load reports
// Load reports
function loadReports() {
    changeReportView();
}

// Change report view
// Change report view
function changeReportView() {
    const reportType = document.getElementById('report-type').value;
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
                <div class="report-month">${periodLabel}</div>
            </div>
            <div class="report-stats">
                <div class="report-stat">
                    <span class="report-stat-icon">💼</span>
                    <span class="report-stat-label">Opening Balance</span>
                    <span class="report-stat-value">₹${parseFloat(report.opening_balance).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon">💰</span>
                    <span class="report-stat-label">Income</span>
                    <span class="report-stat-value positive">₹${parseFloat(report.income).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon">💸</span>
                    <span class="report-stat-label">Expenses</span>
                    <span class="report-stat-value negative">₹${parseFloat(report.expenses).toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon">📊</span>
                    <span class="report-stat-label">Profit/Loss</span>
                    <span class="report-stat-value ${profitClass}">₹${profit.toFixed(2)}</span>
                </div>
                <div class="report-stat">
                    <span class="report-stat-icon">🏦</span>
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
    }, 200);
    setTimeout(() => {
        removeBadgeElements();
    }, 500);
}

// Function to aggressively remove badge elements
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

// Remove the generateReport function as it's no longer needed


// Category Management Functions
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
    if (!confirm(`Are you sure you want to delete the category "${name}"?`)) {
        return;
    }

    fetch(`api/delete_income_category.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCategories();
            } else {
                alert(data.error || 'Failed to delete category');
            }
        })
        .catch(error => console.error('Error:', error));
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
    if (!confirm(`Are you sure you want to delete the category "${name}"?`)) {
        return;
    }

    fetch(`api/delete_expense_category.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadExpenseCategories();
            } else {
                alert(data.error || 'Failed to delete category');
            }
        })
        .catch(error => console.error('Error:', error));
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
            <td>${log.role}</td>
            <td>${log.month}</td>
            <td>${log.payment_mode}</td>
            <td style="font-weight: 600;">₹${amount.toFixed(2)}</td>
            <td><span class="badge ${statusClass}">${log.status}</span></td>
            <td>
                <button class="btn-action btn-edit-small" onclick="openEditSalaryModal(${log.id}, '${log.employee_name}', '${log.role}', '${log.month}', ${log.amount}, '${log.payment_date}', '${log.payment_mode}', '${log.status}')" title="Edit">✏️</button>
                <button class="btn-action btn-delete-small" onclick="deleteSalaryLog(${log.id})" title="Delete">🗑️</button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Update salary statistics
function updateSalaryStats(logs) {
    // Filter for 'Paid' status only if needed, or just sum all
    // Let's sum all "Paid" ones
    const paidLogs = logs.filter(log => log.status === 'Paid');
    const totalPaid = paidLogs.reduce((sum, log) => sum + parseFloat(log.amount), 0);

    // Unique employees (simple count based on names)
    const employees = new Set(logs.map(log => log.employee_name));

    document.getElementById('total-salary-paid').textContent = '₹' + totalPaid.toFixed(2);
    document.getElementById('total-employees-paid').textContent = employees.size;

    // Calculate This Month's Salary Paid
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();

    const thisMonthPaid = paidLogs.filter(log => {
        const d = new Date(log.payment_date);
        return d.getMonth() === currentMonth && d.getFullYear() === currentYear;
    }).reduce((sum, log) => sum + parseFloat(log.amount), 0);

    const thisMonthEl = document.getElementById('salary-paid-this-month');
    if (thisMonthEl) {
        thisMonthEl.textContent = '₹' + thisMonthPaid.toFixed(2);
    }
}

// Modal functions for Salary
function showAddSalaryModal() {
    document.getElementById('addSalaryModal').classList.add('show');
    // Set default date to today
    document.getElementById('salaryPaymentDate').valueAsDate = new Date();
    loadEmployees(); // Refresh list
}

function closeAddSalaryModal() {
    document.getElementById('addSalaryModal').classList.remove('show');
    document.getElementById('salaryForm').reset();
}

// Submit salary form
function submitSalary(event) {
    event.preventDefault();

    const formData = new FormData();
    // Get name from dropdown text or value? Backend expects Name.
    // We'll store ID in value, Name in text? Or just Name in value?
    // Let's see how I populate it. I will populate Value=ID, Text=Name.
    // So I need to get the Text from the selected option.
    const empSelect = document.getElementById('salaryEmployeeSelect');
    const empName = empSelect.options[empSelect.selectedIndex].text;

    if (empSelect.value === "") {
        alert("Please select an employee");
        return;
    }

    formData.append('employee_name', empName);
    formData.append('role', document.getElementById('salaryRole').value);
    formData.append('month', document.getElementById('salaryMonth').value);
    formData.append('payment_date', document.getElementById('salaryPaymentDate').value);
    formData.append('amount', document.getElementById('salaryAmount').value);
    formData.append('payment_mode', document.getElementById('salaryPaymentMode').value);
    formData.append('status', document.getElementById('salaryStatus').value);

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
                } else {
                    alert('Error: ' + (data.error || 'Failed to add salary record'));
                }
            } catch (e) {
                console.error('Server Error:', text);
                alert('Server Error: ' + text.substring(0, 100) + '...');
            }
        })
        // .catch(error => console.error('Error:', error)); // Already handled above somewhat, but good to keep
        .catch(error => console.error('Network Error:', error));
}

// Delete salary log (Optional, but good ui practice)
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

// Window click to close modal needs update
// We'll add a new listener instead of modifying the existing one to avoid complexity
window.addEventListener('click', function (event) {
    const salaryModal = document.getElementById('addSalaryModal');
    const employeeModal = document.getElementById('employeeModal');
    if (event.target == salaryModal) {
        closeAddSalaryModal();
    }
    if (event.target == employeeModal) {
        closeEmployeeModal();
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
    const container = document.getElementById('loan-list');
    if (loans.length === 0) {
        container.innerHTML = '<p style="padding: 20px; text-align: center; color: #64748b;">No active loans.</p>';
        return;
    }

    let html = '<table class="table"><thead><tr><th>Creditor</th><th>Progress</th><th>Amount</th><th>Interest</th><th>Actions</th></tr></thead><tbody>';

    loans.forEach(loan => {
        // Calculate Reminder Logic
        const today = new Date();
        const start = new Date(loan.start_date);
        const dayOfMonth = start.getDate();
        let showReminder = false;

        if (loan.status === 'Active' && parseFloat(loan.interest_rate) > 0) {
            // User wants this constant
            showReminder = true;
        }

        // Progress Calculation
        const principal = parseFloat(loan.principal_amount);
        const paid = parseFloat(loan.paid_amount || 0);
        const outstanding = principal - paid;
        const percent = Math.min((paid / principal) * 100, 100);

        html += `<tr>
            <td>
                <div style="font-weight: 500;">${loan.creditor_name}</div>
                <div style="font-size: 12px; color: #64748b;">${loan.description || ''}</div>
                ${showReminder ? `<div style="margin-top:5px; padding: 5px; background: #fee2e2; color: #ef4444; border-radius: 4px; font-size: 11px;">
                    Have you paid your month interest? <button onclick="openPayInterestModal(${loan.id}, ${outstanding}, ${loan.interest_rate}, '${loan.interest_type || 'Monthly'}', '${loan.last_interest_payment_date || ''}')" style="border:none; background:none; color: #b91c1c; text-decoration: underline; cursor: pointer; font-weight: bold;">Yes</button>
                </div>` : ''}
            </td>
            <td style="width: 200px;">
                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;">
                    <span>₹${paid.toFixed(0)} Paid</span>
                    <span>${percent.toFixed(0)}%</span>
                </div>
                <div style="width: 100%; height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden;">
                    <div style="width: ${percent}%; height: 100%; background: #22c55e;"></div>
                </div>
            </td>
            <td>
                <div style="font-weight: 600;">₹${principal.toFixed(2)}</div>
                <div style="font-size: 11px; color: #64748b;">Due: ₹${outstanding.toFixed(2)}</div>
            </td>
            <td>${loan.interest_rate}%<br><span style="font-size:10px; color:#64748b;">${loan.interest_type || 'Monthly'}</span></td>
            <td>
                <button class="btn-action btn-edit-small" onclick="openPayInterestModal(${loan.id}, ${outstanding}, ${loan.interest_rate}, '${loan.interest_type || 'Monthly'}', '${loan.last_interest_payment_date || ''}')" title="Pay Interest">💸</button>
                <button class="btn-action btn-edit-small" onclick="openRepayModal(${loan.id}, ${outstanding})" title="Repay Principal">💰</button>
                <button class="btn-action btn-edit-small" onclick="openHistoryModal(${loan.id})" title="View History">👁️</button>
                <button class="btn-action btn-edit-small" onclick="openEditLoanModal(${loan.id}, '${loan.creditor_name}', ${loan.principal_amount}, ${loan.interest_rate}, '${loan.interest_type || 'Monthly'}', '${loan.start_date}', '${loan.status}', '${(loan.description || '').replace(/'/g, "\\'")}')" title="Edit">✏️</button>
                <button class="btn-action btn-delete-small" onclick="deleteLoan(${loan.id})" title="Delete">🗑️</button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

function updateLoanStats(loans) {
    // 1. Total Loan Taken
    const totalTaken = loans.reduce((sum, loan) => sum + parseFloat(loan.principal_amount), 0);
    document.getElementById('total-loan-taken-amount').textContent = '₹' + totalTaken.toFixed(2);
    document.getElementById('total-loan-taken-count').textContent = loans.length;

    // 2. Active Loans
    const activeLoans = loans.filter(l => l.status === 'Active');
    const activeAmount = activeLoans.reduce((sum, loan) => sum + (parseFloat(loan.principal_amount) - parseFloat(loan.paid_amount || 0)), 0);
    document.getElementById('active-loan-amount').textContent = '₹' + activeAmount.toFixed(2);
    document.getElementById('active-loan-count').textContent = activeLoans.length;

    // 3. Monthly Liability (Active Loans Only)
    let monthlyLiability = 0;
    activeLoans.forEach(loan => {
        let rate = parseFloat(loan.interest_rate);
        if (loan.interest_type === 'Annual') {
            rate = rate / 12;
        }
        // Interest usually on outstanding principal? Or original?
        // Standard personal loans: interest on OUTSTANDING.
        const outstanding = parseFloat(loan.principal_amount) - parseFloat(loan.paid_amount || 0);
        monthlyLiability += (outstanding * rate) / 100;
    });
    document.getElementById('monthly-interest-liability').textContent = '₹' + monthlyLiability.toFixed(2);

    // 4. Total Interest Paid (Fetch from API)
    fetch('api/loan_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-interest-paid').textContent = '₹' + data.total_interest_paid.toFixed(2);
            }
        })
        .catch(e => console.error('Stats Error:', e));
}

function showAddLoanModal() {
    document.getElementById('addLoanModal').classList.add('show');
    document.getElementById('loanStartDate').valueAsDate = new Date();
    // Default to Monthly
    const radios = document.getElementsByName('interestType');
    for (let r of radios) { if (r.value === 'Monthly') r.checked = true; }
}

function closeAddLoanModal() {
    document.getElementById('addLoanModal').classList.remove('show');
    document.getElementById('loanForm').reset();
}

function submitLoan(event) {
    event.preventDefault();

    const formData = new FormData();
    formData.append('creditor_name', document.getElementById('loanCreditor').value);
    formData.append('principal_amount', document.getElementById('loanAmount').value);
    formData.append('interest_rate', document.getElementById('loanInterest').value);
    formData.append('start_date', document.getElementById('loanStartDate').value);
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
                    html += `<tr>
                        <td>${item.date}</td>
                        <td>${item.description}</td>
                        <td style="font-weight:600; color:#ef4444;">-₹${parseFloat(item.amount).toFixed(2)}</td>
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
    const roleInput = document.getElementById('newEmployeeRoleInput');
    const name = nameInput.value.trim();
    const role = roleInput.value.trim();

    if (!name) {
        alert("Please enter employee name");
        return;
    }

    const formData = new FormData();
    formData.append('name', name);
    formData.append('role', role);

    fetch('api/add_employee.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                nameInput.value = '';
                roleInput.value = '';
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

function onSalaryEmployeeSelect() {
    const select = document.getElementById('salaryEmployeeSelect');
    const roleInput = document.getElementById('salaryRole');

    const selectedOption = select.options[select.selectedIndex];
    if (selectedOption && selectedOption.dataset.role) {
        roleInput.value = selectedOption.dataset.role;
    }
}