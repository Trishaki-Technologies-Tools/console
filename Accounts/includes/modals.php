<div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Income Categories</h3>
                <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Category</label>
                    <div class="category-form">
                        <input type="text" id="newCategoryInput" class="category-input" placeholder="Category Name">
                        <button class="btn-add-category" onclick="addCategory()">+ Add</button>
                    </div>
                </div>
                <div class="category-list" id="categoryList"></div>
            </div>
        </div>
    </div>

    <!-- Expense Category Management Modal -->
    <div id="expenseCategoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Expense Categories</h3>
                <button class="modal-close" onclick="closeExpenseCategoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Category</label>
                    <div class="category-form">
                        <input type="text" id="newExpenseCategoryInput" class="category-input"
                            placeholder="Category Name">
                        <button class="btn-add-category" onclick="addExpenseCategory()">+ Add</button>
                    </div>
                </div>
                <div class="category-list" id="expenseCategoryList"></div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Management Modal -->
    <div id="paymentModesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Payment Methods</h3>
                <button class="modal-close" onclick="closePaymentModesModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Payment Method</label>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">
                        <input type="text" id="newPaymentModeInput" class="category-input"
                            placeholder="Payment Method Name (e.g. HDFC Bank, Razorpay, Cash)" style="width: 100%;" oninput="handlePaymentModeNameInput(this)">
                        
                        <div id="newPaymentModeTypeRow" style="display: flex; gap: 10px; align-items: flex-end;">
                            <div style="flex: 1;">
                                <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; text-transform: uppercase;">Method Type</label>
                                <select id="newPaymentModeTypeSelect" class="category-input" style="width: 100%;" onchange="handlePaymentModeTypeChange(this.value)">
                                    <option value="bank">Bank</option>
                                    <option value="merchant">Merchant</option>
                                </select>
                                <span id="cashNoTypeNotice" style="display: none; font-size: 12px; color: #64748b; font-style: italic;">(No type for Cash)</span>
                            </div>
                            <div id="settlementBankCol" style="flex: 1; display: none;">
                                <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; text-transform: uppercase;">Settlement Bank</label>
                                <select id="newPaymentModeSettlementBankSelect" class="category-input" style="width: 100%;">
                                    <option value="">Select Bank</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label style="font-size: 11px; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 4px; text-transform: uppercase;">Opening Bal (₹)</label>
                                <input type="number" id="newPaymentModeBalanceInput" class="category-input"
                                    placeholder="0.00" step="0.01" style="width: 100%;">
                            </div>
                        </div>
                        <button class="btn-add-category" onclick="addPaymentMode()" style="width: 100%; margin-top: 5px;">+ Add Payment Method</button>
                    </div>
                </div>
                <div class="category-list" id="paymentModeList" style="max-height: 350px;"></div>
            </div>
        </div>
    </div>

    <!-- Employee Management Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Manage Employees</h3>
                <button class="modal-close" onclick="closeEmployeeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-form-section">
                    <label class="category-form-label">Add New Employee</label>
                    <div class="category-form" style="display: flex; flex-direction: column; gap: 10px;">
                        <input type="text" id="newEmployeeNameInput" class="category-input" placeholder="Employee Name"
                            style="width: 100%;">
                        <button class="btn-add-category" onclick="addEmployee()" style="width: 100%;">+ Add
                            Employee</button>
                    </div>
                </div>
                <div class="category-list" id="employeeList"></div>
            </div>
        </div>
    </div>

    <!-- Add Expense Modal -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Add Expense</h3>
                <button class="modal-close" onclick="closeAddExpenseModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="expenseForm" onsubmit="submitExpense(event)" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <label class="form-label" style="margin-bottom: 0;">Date & Time <span
                                        class="required">*</span></label>
                                <label
                                    style="display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-muted); cursor: pointer; user-select: none;">
                                    <input type="checkbox" id="expenseHasTime"> Include Time
                                </label>
                            </div>
                            <input type="date" id="expenseDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="expenseAmount" class="form-input" placeholder="0.00" step="0.01"
                                min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="expenseCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="expenseDescription" class="form-input"
                                placeholder="Enter description" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="expensePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="expenseAttachment" class="form-input">
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddExpenseModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Income Modal -->
    <div id="addIncomeModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Add Income</h3>
                <button class="modal-close" onclick="closeAddIncomeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="incomeForm" onsubmit="submitIncome(event)" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <label class="form-label" style="margin-bottom: 0;">Date & Time <span
                                        class="required">*</span></label>
                                <label
                                    style="display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-muted); cursor: pointer; user-select: none;">
                                    <input type="checkbox" id="incomeHasTime"> Include Time
                                </label>
                            </div>
                            <input type="date" id="incomeDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="incomeAmount" class="form-input" placeholder="0.00" step="0.01"
                                min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="incomeCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="incomeDescription" class="form-input" placeholder="Enter description"
                                required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="incomePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="incomeAttachment" class="form-input">
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddIncomeModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Salary Modal -->
    <div id="addSalaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pay Salary</h3>
                <button class="modal-close" onclick="closeAddSalaryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="salaryForm" onsubmit="submitSalary(event)">
                    <div class="form-group">
                        <label class="form-label">Employee Name <span class="required">*</span></label>
                        <select id="salaryEmployeeSelect" class="form-select" required>
                            <option value="">Select Employee</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Month <span class="required">*</span></label>
                        <input type="month" id="salaryMonth" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="salaryPaymentDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount <span class="required">*</span></label>
                        <input type="number" id="salaryAmount" class="form-input" placeholder="0.00" step="0.01" min="0"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode <span class="required">*</span></label>
                        <select id="salaryPaymentMode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddSalaryModal()">Cancel</button>
                        <button type="submit" class="btn-save">Pay & Generate Slip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Edit Salary Modal -->
    <div id="editSalaryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Salary Log</h3>
                <button class="modal-close" onclick="closeEditSalaryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editSalaryForm" onsubmit="submitEditSalary(event)">
                    <input type="hidden" id="editSalaryId">
                    <div class="form-group">
                        <label class="form-label">Employee Name <span class="required">*</span></label>
                        <input type="text" id="editSalaryEmployeeName" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role/Designation</label>
                        <input type="text" id="editSalaryRole" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Month <span class="required">*</span></label>
                        <input type="month" id="editSalaryMonth" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="editSalaryPaymentDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount <span class="required">*</span></label>
                        <input type="number" id="editSalaryAmount" class="form-input" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode</label>
                        <select id="editSalaryPaymentMode" class="form-select">
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="editSalaryStatus" class="form-select">
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditSalaryModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Loan Modal -->
    <div id="addLoanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Loan</h3>
                <button class="modal-close" onclick="closeAddLoanModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="loanForm" onsubmit="submitLoan(event)">
                    <div class="form-group">
                        <label class="form-label">Source Type <span class="required">*</span></label>
                        <select id="loanSourceType" class="form-select" required onchange="updateLoanSourceLabel()">
                            <option value="">Select Source</option>
                            <option value="Person">Person</option>
                            <option value="Bank">Bank</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" id="loanCreditorLabel">Name <span class="required">*</span></label>
                        <input type="text" id="loanCreditor" class="form-input" placeholder="Enter name" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Total Loan Amount <span class="required">*</span></label>
                        <input type="number" id="loanAmount" class="form-input" placeholder="0.00" step="0.01" min="0"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Charges Deducted (Optional)</label>
                        <input type="number" id="loanCharges" class="form-input" placeholder="0.00" step="0.01" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Interest Rate (% Annually)</label>
                        <input type="number" id="loanInterest" class="form-input" placeholder="0.00" step="0.01"
                            min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" id="loanStartDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode <span class="required">*</span></label>
                        <select id="loanPaymentMode" class="form-select" required>
                            <option value="">Select Payment Mode</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="loanDescription" class="form-input" rows="2"
                            placeholder="Additional details"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddLoanModal()">Cancel</button>
                        <button type="submit" class="btn-save">Save Loan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Edit Loan Modal -->
    <div id="editLoanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Loan</h3>
                <button class="modal-close" onclick="closeEditLoanModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editLoanForm" onsubmit="submitEditLoan(event)">
                    <input type="hidden" id="editLoanId">
                    <div class="form-group">
                        <label class="form-label">Creditor Name <span class="required">*</span></label>
                        <input type="text" id="editLoanCreditor" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Principal Amount <span class="required">*</span></label>
                        <input type="number" id="editLoanAmount" class="form-input" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Interest Rate (%)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" id="editLoanInterest" class="form-input" step="0.01" min="0"
                                style="flex: 1;">
                            <div style="display: flex; gap: 5px;">
                                <label><input type="radio" name="editInterestType" value="Monthly"> Monthly</label>
                                <label><input type="radio" name="editInterestType" value="Annual"> Annual</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Date <span class="required">*</span></label>
                        <input type="date" id="editLoanStartDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select id="editLoanStatus" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea id="editLoanDescription" class="form-input" rows="3"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditLoanModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Loan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pay Interest Modal -->
    <div id="payInterestModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pay Interest</h3>
                <button class="modal-close" onclick="closePayInterestModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="payInterestForm" onsubmit="submitInterestPayment(event)">
                    <input type="hidden" id="payInterestLoanId">
                    <p id="payInterestText" style="margin-bottom: 20px; color: #64748b;">Record interest payment for
                        this month.</p>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="payInterestDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Interest Amount (Editable)</label>
                        <input type="number" id="payInterestAmountDisplay" class="form-input" step="0.01" min="0"
                            required style="font-weight: bold; color: #0f172a;">
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closePayInterestModal()">Cancel</button>
                        <button type="submit" class="btn-save">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Repay Loan Modal -->
    <div id="repayLoanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Repay Principal</h3>
                <button class="modal-close" onclick="closeRepayModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="repayLoanForm" onsubmit="submitRepayment(event)">
                    <input type="hidden" id="repayLoanId">
                    <p style="margin-bottom: 15px; color: #64748b;">
                        Outstanding Principal: <strong id="repayOutstandingDisplay">₹0.00</strong>
                    </p>

                    <div class="form-group">
                        <label class="form-label">Repayment Amount <span class="required">*</span></label>
                        <input type="number" id="repayAmount" class="form-input" step="0.01" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="required">*</span></label>
                        <input type="date" id="repayDate" class="form-input" required>
                    </div>

                    <div class="alert-box"
                        style="margin-top: 15px; font-size: 13px; color: #1e293b; background: #f1f5f9; padding: 10px; border-radius: 4px;">
                        Note: Full payment will automatically close the loan.
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeRepayModal()">Cancel</button>
                        <button type="submit" class="btn-save">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Add Report Modal -->
    <div id="addReportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Report</h3>
                <button class="modal-close" onclick="closeAddReportModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addReportForm" onsubmit="submitAddReport(event)">
                    <div class="form-group">
                        <label class="form-label">Month <span class="required">*</span></label>
                        <input type="month" id="reportMonth" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" id="reportOpeningBalance" class="form-input" placeholder="0.00" step="0.01"
                            value="0.00">
                        <small class="form-hint">Set to 0 if this is not the first report.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Payment Mode</label>
                        <select id="reportPaymentMode" class="form-select">
                            <option value="HDFC Bank">HDFC Bank</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeAddReportModal()">Cancel</button>
                        <button type="submit" class="btn-save">Create Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Income Modal -->
    <div id="editIncomeModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Edit Income</h3>
                <button class="modal-close" onclick="closeEditIncomeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editIncomeForm" onsubmit="submitEditIncome(event)" enctype="multipart/form-data">
                    <input type="hidden" id="editIncomeId">

                    <div class="form-grid">
                        <div class="form-group">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <label class="form-label" style="margin-bottom: 0;">Date & Time <span
                                        class="required">*</span></label>
                                <label
                                    style="display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-muted); cursor: pointer; user-select: none;">
                                    <input type="checkbox" id="editIncomeHasTime"> Include Time
                                </label>
                            </div>
                            <input type="date" id="editIncomeDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="editIncomeAmount" class="form-input" placeholder="0.00" step="0.01"
                                min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="editIncomeCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="editIncomeDescription" class="form-input"
                                placeholder="Enter description" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="editIncomePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="editIncomeAttachment" class="form-input">
                            <small class="form-hint" id="editIncomeAttachmentHint" style="color: #6366f1;"></small>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditIncomeModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Income</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal -->
    <div id="editExpenseModal" class="modal">
        <div class="modal-content wide">
            <div class="modal-header">
                <h3>Edit Expense</h3>
                <button class="modal-close" onclick="closeEditExpenseModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editExpenseForm" onsubmit="submitEditExpense(event)" enctype="multipart/form-data">
                    <input type="hidden" id="editExpenseId">

                    <div class="form-grid">
                        <div class="form-group">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                                <label class="form-label" style="margin-bottom: 0;">Date & Time <span
                                        class="required">*</span></label>
                                <label
                                    style="display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-muted); cursor: pointer; user-select: none;">
                                    <input type="checkbox" id="editExpenseHasTime"> Include Time
                                </label>
                            </div>
                            <input type="date" id="editExpenseDate" class="form-input" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Amount <span class="required">*</span></label>
                            <input type="number" id="editExpenseAmount" class="form-input" placeholder="0.00"
                                step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Category Type <span class="required">*</span></label>
                            <select id="editExpenseCategorySelect" class="form-select" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Description <span class="required">*</span></label>
                            <input type="text" id="editExpenseDescription" class="form-input"
                                placeholder="Enter description" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Payment Mode <span class="required">*</span></label>
                            <select id="editExpensePaymentMode" class="form-select" required>
                                <option value="">Select Payment Mode</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Attachment (Optional)</label>
                            <input type="file" id="editExpenseAttachment" class="form-input">
                            <small class="form-hint" id="editExpenseAttachmentHint" style="color: #6366f1;"></small>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeEditExpenseModal()">Cancel</button>
                        <button type="submit" class="btn-save">Update Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div id="loanHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Loan Payment History</h3>
                <button class="modal-close" onclick="closeHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-container">
                    <table class="table" id="loanHistoryTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Non-GST Invoice Modal -->
    <div id="nonGstInvoiceModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px; align-items: center; width: 100%; max-width: 300px;">
                    <button type="button" class="ledger-tab-btn" onclick="switchToGstModal()" style="border: none;">GST Invoice (18%)</button>
                    <button type="button" class="ledger-tab-btn active" style="border: none;">Non-GST Invoice</button>
                </div>
                <button class="modal-close" onclick="closeNonGstModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="nonGstInvoiceForm" onsubmit="generateNonGstInvoice(event)">
                    <div class="form-group">
                        <label class="form-label">Bill To Name <span class="required">*</span></label>
                        <input type="text" id="nonGstBillToName" class="form-input" placeholder="Enter customer name or select from list" list="clientDatalist" oninput="autofillClientDetails('nonGst')"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="nonGstPhone" class="form-input" placeholder="Enter 10-digit phone number" required
                            pattern="[0-9]{10}" maxlength="10" minlength="10"
                            oninput="this.value=this.value.replace(/[^0-9]/g,''); checkExistingUser(this.value, 'non-gst')">
                        <div id="existingUserNonGst" style="margin-top: 10px;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="nonGstEmail" class="form-input" placeholder="Enter email (optional)">
                    </div>

                    

                    <div class="form-group">
                        <label class="form-label">Invoice Items <span class="required">*</span></label>
                        <div id="nonGstItemsContainer">
                            <div class="invoice-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="text" class="form-input nongst-item-desc" placeholder="Description"
                                    required style="flex: 3;">
                                <input type="number" class="form-input nongst-item-amount" placeholder="Amount"
                                    step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
                                <button type="button" class="btn-add-item" onclick="addNonGstItem()">+</button>
                            </div>
                        </div>
                        <button type="button" id="btnNonGstDone" class="btn-primary"
                            style="margin-top: 10px; background-color: #0ea5e9; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;"
                            onclick="clickNonGstDone()">Done</button>
                    </div>

                    <!-- Payment & Summary Section -->
                    <div id="nonGstPaymentSummarySection"
                        style="display: none; border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 15px;">
                        <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 15px; font-weight: 600;">Payment &
                            Summary</h4>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Mode <span class="required">*</span></label>
                                <select id="nonGstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                </select>
                            </div>

                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Date <span class="required">*</span></label>
                                <input type="date" id="nonGstPaymentDate" class="form-input">
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Amount Paid <span class="required">*</span></label>
                                <input type="number" id="nonGstAmountPaid" class="form-input"
                                    placeholder="Enter amount paid" step="0.01" min="0" oninput="updateNonGstSummary()">
                            </div>
                        </div>

                        <!-- Summary Box -->
                        <div
                            style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Total Amount:</span>
                                <span id="summaryNonGstTotal" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Amount Paid:</span>
                                <span id="summaryNonGstPaid" style="font-weight: 700; color: #10b981;">₹0.00</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 600;">Balance Due:</span>
                                <span id="summaryNonGstDue" style="font-weight: 700; color: #ef4444;">₹0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeNonGstModal()">Cancel</button>
                        <button type="submit" id="btnNonGstGenerate" class="btn-save" style="display: none;">Generate
                            Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- GST Invoice Modal -->
    <div id="gstInvoiceModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px; align-items: center; width: 100%; max-width: 300px;">
                    <button type="button" class="ledger-tab-btn active" style="border: none;">GST Invoice (18%)</button>
                    <button type="button" class="ledger-tab-btn" onclick="switchToNonGstModal()" style="border: none;">Non-GST Invoice</button>
                </div>
                <button class="modal-close" onclick="closeGstModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="gstInvoiceForm" onsubmit="generateGstInvoice(event)">
                    <div class="form-group">
                        <label class="form-label">Bill To Name <span class="required">*</span></label>
                        <input type="text" id="gstBillToName" class="form-input" placeholder="Enter customer name or select from list" list="clientDatalist" oninput="autofillClientDetails('gst')"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="gstPhone" class="form-input" placeholder="Enter 10-digit phone number" required
                            pattern="[0-9]{10}" maxlength="10" minlength="10"
                            oninput="this.value=this.value.replace(/[^0-9]/g,''); checkExistingUser(this.value, 'gst')">
                        <div id="existingUserGst" style="margin-top: 10px;"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">GST Number <span class="required">*</span></label>
                        <input type="text" id="gstModalNumber" class="form-input" placeholder="Enter GST number"
                            required pattern="[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}">
                        <small style="color: #64748b; font-size: 12px;">Format: 22AAAAA0000A1Z5</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="gstEmail" class="form-input" placeholder="Enter email (optional)">
                    </div>

                    

                    <div class="form-group">
                        <label class="form-label">Invoice Items <span class="required">*</span></label>
                        <div id="gstItemsContainer">
                            <div class="invoice-item-row-gst" style="display: flex; gap: 8px; margin-bottom: 8px; align-items: center;">
                                <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
                                <select class="form-select gst-sac" style="flex: 2;">
                                    <option value="">Select SAC</option>
                                    <option value="9983">9983 - Project and Internship</option>
                                    <option value="998314">998314 - For Client Projects</option>
                                    <option value="998313">998313 - Consultancy</option>
                                    <option value="998315">998315 - For Hosting</option>
                                </select>
                                <input type="number" class="form-input gst-total-incl" placeholder="Charges (excl tax)" step="0.01" min="0" required style="flex: 1.5;" oninput="onGstItemAmountChange()">
                                <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                                    <input type="checkbox" class="gst-desc-check"> Desc.%
                                </label>
                                <button type="button" class="btn-add-item" onclick="addGstItem()">+</button>
                            </div>
                        </div>
                        <button type="button" id="btnGstDone" class="btn-primary"
                            style="margin-top: 10px; background-color: #0ea5e9; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;"
                            onclick="clickGstDone()">Done</button>
                    </div>

                    <!-- Payment & Summary Section -->
                    <div id="gstPaymentSummarySection"
                        style="display: none; border-top: 1px solid #e2e8f0; margin-top: 20px; padding-top: 15px;">
                        <h4 style="margin-bottom: 15px; color: #1e293b; font-size: 15px; font-weight: 600;">Payment &
                            Summary</h4>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Mode <span class="required">*</span></label>
                                <select id="gstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                </select>
                            </div>

                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Payment Date <span class="required">*</span></label>
                                <input type="date" id="gstPaymentDate" class="form-input">
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-bottom: 15px; align-items: flex-end;">
                            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                                <label class="form-label">Amount Paid <span class="required">*</span></label>
                                <input type="number" id="gstAmountPaid" class="form-input"
                                    placeholder="Enter amount paid" step="0.01" min="0" oninput="updateGstSummary()">
                            </div>
                        </div>

                        <!-- Summary Box -->
                        <div
                            style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-bottom: 15px;">
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Charges (Excl. Tax):</span>
                                <span id="summaryGstCharges" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">GST (18%):</span>
                                <span id="summaryGstTax" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Total Amount (Incl. Tax):</span>
                                <span id="summaryGstTotal" style="font-weight: 700; color: #0f172a;">₹0.00</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 500;">Amount Paid:</span>
                                <span id="summaryGstPaid" style="font-weight: 700; color: #10b981;">₹0.00</span>
                            </div>
                            <div
                                style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 8px; font-size: 13px;">
                                <span style="color: #64748b; font-weight: 600;">Balance Due:</span>
                                <span id="summaryGstDue" style="font-weight: 700; color: #ef4444;">₹0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeGstModal()">Cancel</button>
                        <button type="submit" id="btnGstGenerate" class="btn-save" style="display: none;">Generate
                            Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Voucher Modal -->
    <div id="voucherModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Payment Voucher Details</h3>
                <button class="modal-close" onclick="closeVoucherModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="voucherForm" onsubmit="generateVoucher(event)">
                    <div class="form-group">
                        <label class="form-label">To whom (Payee Name) <span class="required">*</span></label>
                        <input type="text" id="voucherPayee" class="form-input"
                            placeholder="Enter name of the person/company" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Amount <span class="required">*</span></label>
                        <input type="number" id="voucherAmount" class="form-input" placeholder="0.00" step="0.01"
                            min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mode of Payment <span class="required">*</span></label>
                        <select id="voucherMode" class="form-select" required>
                            <option value="Cash" selected>Cash</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date <span class="required">*</span></label>
                        <input type="date" id="voucherDate" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Being (Description) <span class="required">*</span></label>
                        <textarea id="voucherDescription" class="form-input" placeholder="Purpose of payment" required
                            style="height: 80px;"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" onclick="closeVoucherModal()">Cancel</button>
                        <button type="submit" class="btn-save">Generate Voucher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add/Edit Quotation Modal -->
    <!-- Add/Edit Quotation Modal -->
    <div id="quotationModal" class="modal">
        <div class="modal-content fullscreen">
            <div class="modal-header"
                style="margin-bottom: 0; padding: 15px 24px; background: #ffffff; border-bottom: 1px solid var(--border-light);">
                <h3 id="quotationModalTitle" style="color: var(--text-main); font-weight: 800; font-size: 18px;">Create
                    Corporate Quotation</h3>
                <button class="modal-close" onclick="closeQuotationModal()">&times;</button>
            </div>
            <input type="hidden" id="quotationId" value="">
            <div class="qb-container">
                <!-- Left Panel: Form Steps -->
                <div class="qb-editor">
                    <div class="qb-tabs">
                        <button type="button" class="qb-tab-btn active" id="tab-btn-info"
                            onclick="switchQuotationTab('info')">1. Client & Project</button>
                        <button type="button" class="qb-tab-btn" id="tab-btn-commercial"
                            onclick="switchQuotationTab('commercial')">2. Commercial Details</button>
                        <button type="button" class="qb-tab-btn" id="tab-btn-scope"
                            onclick="switchQuotationTab('scope')">3. Scope of Work</button>
                    </div>

                    <div class="qb-tab-content">
                        <!-- Step 1: Client & Project Info -->
                        <div id="step-info" class="qb-step-panel active">
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--text-main); font-size: 15px;">
                                Client Information</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <input type="tel" id="qClientPhone" class="form-input" required
                                        pattern="[0-9]{10}" maxlength="10" minlength="10"
                                        placeholder="10-digit phone number"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        style="color: var(--text-main); background: #fff;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Client Name <span class="required">*</span></label>
                                    <input type="text" id="qClientName" class="form-input" required
                                        placeholder="e.g. John Doe" style="color: var(--text-main); background: #fff;">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" id="qClientEmail" class="form-input"
                                        placeholder="e.g. client@example.com"
                                        style="color: var(--text-main); background: #fff;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GST Number</label>
                                    <input type="text" id="qClientGst" class="form-input"
                                        placeholder="e.g. 29ABCDE1234F1Z5"
                                        style="color: var(--text-main); background: #fff;">
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 25px;">
                                <div class="form-group">
                                    <label class="form-label">Billing Address</label>
                                    <input type="text" id="qClientAddress" class="form-input"
                                        placeholder="Enter client address"
                                        style="color: var(--text-main); background: #fff;">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Quotation Date <span class="required">*</span></label>
                                    <input type="date" id="qDate" class="form-input" required
                                        style="color: var(--text-main); background: #fff;">
                                </div>
                            </div>

                            <h4
                                style="margin-top: 20px; margin-bottom: 15px; color: var(--text-main); font-size: 15px;">
                                Project Details</h4>
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label class="form-label">Project Name <span class="required">*</span></label>
                                <input type="text" id="qProjectName" class="form-input" required
                                    placeholder="e.g. E-Commerce Website Development"
                                    style="color: var(--text-main); background: #fff;">
                            </div>
                            <div class="form-group" style="margin-bottom: 10px;">
                                <label class="form-label">Project Description</label>
                                <textarea id="qProjectDescription" class="form-input" rows="3"
                                    placeholder="Brief outline of the project objectives and scope..."
                                    style="color: var(--text-main); background: #fff; resize: vertical;"></textarea>
                            </div>
                            <div
                                style="margin-top: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" id="qIncludeScope" checked onchange="toggleScopeStepVisibility()"
                                    style="width: 18px; height: 18px; cursor: pointer;">
                                <label for="qIncludeScope"
                                    style="font-size: 13px; font-weight: 600; color: var(--text-main); cursor: pointer; user-select: none;">Include
                                    Project Scope Page in PDF</label>
                            </div>
                        </div>

                        <!-- Step 2: Commercial Details -->
                        <div id="step-commercial" class="qb-step-panel">
                            <h4 style="margin-top: 0; margin-bottom: 15px; color: var(--text-main); font-size: 15px;">
                                Commercial Line Items</h4>

                            <div class="table-responsive" style="overflow: visible; margin-bottom: 15px;">
                                <table style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>Description</th>
                                            <th style="width: 150px; text-align: right; padding-right: 15px;">Amount (₹)
                                            </th>
                                            <th style="width: 50px; text-align: center;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="qItemsTableBody" class="sortable-list">
                                        <!-- Items loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <button type="button" class="btn-secondary" onclick="addQuotationItemRowNew()"
                                style="margin-bottom: 25px;">+ Add Item</button>

                            <div
                                style="background: #f1f5f9; padding: 20px; border-radius: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="display: flex; flex-direction: column; gap: 12px;">
                                    <div class="form-group">
                                        <label class="form-label">Discount Amount (₹)</label>
                                        <input type="number" id="qDiscountInput" class="form-input" min="0" value="0"
                                            step="0.01" oninput="calculateQuotationSummaryNew()"
                                            style="color: var(--text-main); background: #fff;">
                                    </div>
                                    <div class="form-group" style="display: none !important;">
                                        <label class="form-label">GST Percentage (%)</label>
                                        <select id="qGstRateInput" class="form-input"
                                            onchange="calculateQuotationSummaryNew()"
                                            style="color: var(--text-main); background: #fff; height: 42px;">
                                            <option value="18" selected>18% (Standard GST)</option>
                                            <option value="12">12%</option>
                                            <option value="5">5%</option>
                                            <option value="0">0% (Without GST / Tax Exempt)</option>
                                        </select>
                                    </div>
                                </div>
                                <div
                                    style="display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-end; text-align: right;">
                                    <div
                                        style="font-size: 14px; color: var(--text-muted); display: flex; justify-content: space-between; width: 100%; max-width: 280px; margin-bottom: 6px;">
                                        <span>Total Amount:</span>
                                        <strong id="qSubtotalVal" style="color: var(--text-main);">₹0.00</strong>
                                    </div>
                                    <div
                                        style="font-size: 14px; color: var(--text-muted); display: flex; justify-content: space-between; width: 100%; max-width: 280px; margin-bottom: 6px;">
                                        <span>Discount:</span>
                                        <strong id="qDiscountVal" style="color: #ef4444;">-₹0.00</strong>
                                    </div>
                                    <div
                                        style="font-size: 14px; color: var(--text-muted); display: none !important; justify-content: space-between; width: 100%; max-width: 280px; margin-bottom: 6px;">
                                        <span id="qCgstLabel">CGST (9%):</span>
                                        <strong id="qCgstVal" style="color: var(--text-main);">₹0.00</strong>
                                    </div>
                                    <div
                                        style="font-size: 14px; color: var(--text-muted); display: none !important; justify-content: space-between; width: 100%; max-width: 280px; margin-bottom: 10px;">
                                        <span id="qSgstLabel">SGST (9%):</span>
                                        <strong id="qSgstVal" style="color: var(--text-main);">₹0.00</strong>
                                    </div>
                                    <div
                                        style="border-top: 2px solid #cbd5e1; padding-top: 10px; width: 100%; max-width: 280px; display: flex; justify-content: space-between; align-items: center;">
                                        <span style="font-weight: 700; color: var(--text-main); font-size: 16px;">Grand
                                            Total:</span>
                                        <strong id="qGrandTotalVal"
                                            style="font-size: 22px; color: var(--primary); font-weight: 800;">₹0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Scope of Work Builder -->
                        <div id="step-scope" class="qb-step-panel">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h4 style="margin: 0; color: var(--text-main); font-size: 15px;">Project Scope Modules
                                </h4>
                                <button type="button" class="btn-primary" onclick="addScopeModule()"
                                    style="padding: 6px 12px; font-size: 12px;">+ Add Module</button>
                            </div>

                            <div id="qScopeModulesList" class="sortable-list" style="margin-bottom: 20px;">
                                <!-- Modules will be populated here -->
                            </div>
                        </div>
                    </div>

                    <div class="modal-actions"
                        style="padding: 15px 24px; border-top: 1px solid var(--border-light); background: #ffffff; margin-top: 0; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <button type="button" class="btn-cancel" onclick="closeQuotationModal()"
                                style="border: 1px solid #cbd5e1;">Cancel</button>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn-secondary" id="btnQuotationPrev" style="display: none;"
                                onclick="navigateQuotationStep(-1)">Back</button>
                            <button type="button" class="btn-primary" id="btnQuotationNext"
                                onclick="navigateQuotationStep(1)">Next</button>
                            <button type="button" class="btn-secondary" id="btnQuotationSaveDraft"
                                onclick="saveQuotationNew('draft')"
                                style="background: #e2e8f0; color: #334155; border: none;">Save Draft</button>
                            <button type="button" class="btn-save" id="btnQuotationSubmit"
                                onclick="saveQuotationNew('sent')" style="display: none;">Save & Generate</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    
    <script src="js/quotation_functions.js?v=2"></script>
    <script>
        function toggleInvoiceDropdown(event) {
            event.preventDefault();
            const dropdown = document.getElementById('invoiceDropdown');
            dropdown.classList.toggle('show');
        }

        function logout() {
            window.location.href = 'api/logout.php';
        }

        // Close Invoice Type Selection Dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('invoiceTypeSelectionDiv');
            const container = document.querySelector('.generate-invoice-container');
            if (dropdown && container && !container.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Add this to handle generic page transitions for dynamic content
        document.querySelectorAll('.dropdown-item, .nav-item:not(.has-dropdown)').forEach(item => {
            item.addEventListener('click', function (e) {
                const page = this.getAttribute('data-page');
                if (page === 'voucher') {
                    if (typeof loadVouchers === 'function') {
                        loadVouchers();
                    }
                }
                if (page === 'payslip') {
                    if (typeof loadPayslips === 'function') {
                        loadPayslips();
                    }
                }
            });
        });
    </script>
    <!-- Payslip Modal -->
    <div id="payslipModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Generate Employee Payslip</h3>
                <button class="modal-close" onclick="closePayslipModal()">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <form id="payslipForm" onsubmit="generatePayslip(event)">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <!-- Employee Info -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600;">Employee Details</legend>
                            <div class="form-group">
                                <label class="form-label">Employee Name <span class="required">*</span></label>
                                <input type="text" id="payEmpName" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Employee ID <span class="required">*</span></label>
                                <input type="text" id="payEmpNo" class="form-input" placeholder="e.g. 2902188" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Month/Year <span class="required">*</span></label>
                                <input type="month" id="payMonth" class="form-input" value="2026-03" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Designation</label>
                                <input type="text" id="payGrade" class="form-input"
                                    placeholder="e.g. Software Engineer">
                            </div>
                        </fieldset>

                        <!-- Bank & Leave -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600;">Bank & Leave</legend>
                            <div class="form-group">
                                <label class="form-label">Bank Name</label>
                                <input type="text" id="payBank" class="form-input" placeholder="e.g. HDFC Bank">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Account No</label>
                                <input type="text" id="payAcc" class="form-input" placeholder="XXXXXXXX4779">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Days Paid</label>
                                <input type="number" id="payDays" class="form-input" value="31">
                            </div>
                        </fieldset>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <!-- Earnings -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600; color: #10b981;">Earnings (₹)</legend>
                            <div class="form-group"><label class="form-label">Basic Salary</label><input type="number"
                                    id="payBasic" class="form-input" value="15000"></div>
                            <div class="form-group"><label class="form-label">HRA</label><input type="number"
                                    id="payHra" class="form-input" value="6000"></div>
                            <div class="form-group"><label class="form-label">Other Allowance</label><input
                                    type="number" id="payOther" class="form-input" value="5000"></div>
                        </fieldset>

                        <!-- Deductions -->
                        <fieldset style="border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px;">
                            <legend style="padding: 0 10px; font-weight: 600; color: #ef4444;">Deductions (₹)</legend>
                            <div class="form-group"><label class="form-label">Provident Fund</label><input type="number"
                                    id="payPf" class="form-input" value="1800"></div>
                            <div class="form-group"><label class="form-label">Health Insurance</label><input
                                    type="number" id="payHealth" class="form-input" value="200"></div>
                        </fieldset>
                    </div>

                    <div class="modal-actions" style="margin-top: 30px;">
                        <button type="button" class="btn-cancel" onclick="closePayslipModal()">Cancel</button>
                        <button type="submit" class="btn-save">Generate Payslip</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Audit Log Details Modal -->
    <div id="auditLogModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 style="color: var(--text-main); font-weight: 800; font-size: 18px;">Audit Log Details</h3>
                <button class="modal-close" onclick="closeAuditLogModal()">&times;</button>
            </div>
            <div class="modal-body" style="padding: 20px 0;">
                <div style="margin-bottom: 15px;">
                    <strong
                        style="color: var(--text-muted); font-size: 11px; display: block; text-transform: uppercase;">Timestamp
                        (IST)</strong>
                    <span id="audit-modal-timestamp" style="font-size: 14px; font-weight: 600;"></span>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong
                        style="color: var(--text-muted); font-size: 11px; display: block; text-transform: uppercase;">User</strong>
                    <span id="audit-modal-user" style="font-size: 14px; font-weight: 600;"></span>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong
                        style="color: var(--text-muted); font-size: 11px; display: block; text-transform: uppercase;">Action</strong>
                    <span id="audit-modal-action" class="status-badge"
                        style="padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase;"></span>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong
                        style="color: var(--text-muted); font-size: 11px; display: block; text-transform: uppercase;">Module
                        / Table</strong>
                    <span id="audit-modal-module" style="font-family: monospace; font-size: 13px;"></span>
                </div>
                <div style="margin-bottom: 15px;">
                    <strong
                        style="color: var(--text-muted); font-size: 11px; display: block; text-transform: uppercase;">Record
                        ID</strong>
                    <span id="audit-modal-record-id" style="font-weight: bold;"></span>
                </div>
                <div>
                    <strong
                        style="color: var(--text-muted); font-size: 11px; display: block; text-transform: uppercase;">Action
                        Details</strong>
                    <p id="audit-modal-details"
                        style="font-size: 13px; color: #000; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1; margin-top: 5px; white-space: pre-wrap; word-break: break-word;">
                    </p>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeAuditLogModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function logout() {
            window.location.href = 'api/logout.php';
        }
    </script>
<datalist id="clientDatalist"></datalist>
    

    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3 id="addClientModalTitle">Add New Client</h3>
                <button class="modal-close" onclick="closeAddClientModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addClientForm" onsubmit="saveNewClient(event)"><input type="hidden" id="editClientId" value="">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" style="display: block; margin-bottom: 8px;">Client Type</label>
                        <style>
                            .client-type-btn { border: none; padding: 8px; border-radius: 6px; font-weight: 500; cursor: pointer; flex: 1; text-align: center; background: transparent; color: #94a3b8; transition: all 0.2s; }
                            .client-type-btn.active { background: #3b82f6; color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                        </style>
                        <div style="display: flex; background: #0f172a; padding: 4px; border: 1px solid #334155; border-radius: 8px; gap: 4px;">
                            <button type="button" class="client-type-btn active" id="btnTypeClient" onclick="setClientType('Client')">Client</button>
                            <button type="button" class="client-type-btn" id="btnTypeStudent" onclick="setClientType('Student')">Student</button>
                        </div>
                        <input type="hidden" id="newClientType" value="Client">
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" id="nameLabel">Client Name <span class="required">*</span></label>
                        <input type="text" id="newClientName" class="form-input" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="newClientPhone" class="form-input" placeholder="10-digit phone number"
                                pattern="[0-9]{10}" maxlength="10" minlength="10"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address (Optional)</label>
                            <input type="email" id="newClientEmail" class="form-input">
                        </div>
                    </div>

                    <!-- Client Specific Fields -->
                    <div id="clientSpecificFields">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">GST Number (Optional)</label>
                            <input type="text" id="newClientGst" class="form-input">
                        </div>
                        
                    </div>

                    <!-- Student Specific Fields -->
                    <div id="studentSpecificFields" style="display: none;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label class="form-label" style="margin-bottom: 0;">College Name</label>
                                <a href="#" onclick="openManageAcademicModal('colleges'); return false;" style="font-size: 12px; color: #3b82f6; text-decoration: none;">Manage</a>
                            </div>
                            <style>
    .custom-select-wrapper { position: relative; user-select: none; }
    .custom-select-display { border: 1px solid #cbd5e1; padding: 10px 15px; border-radius: 6px; background: #fff; cursor: pointer; font-size: 14px; color: #0f172a; display: flex; justify-content: space-between; align-items: center; }
    .custom-select-display::after { content: "▼"; font-size: 10px; color: #64748b; }
    .custom-select-options { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 4px; max-height: 160px; overflow-y: auto; z-index: 50; display: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .custom-select-options.show { display: block; }
    .custom-select-option { padding: 10px 15px; cursor: pointer; font-size: 14px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .custom-select-option:last-child { border-bottom: none; }
    .custom-select-option:hover { background: #f8fafc; color: #3b82f6; }
</style>
                            <div class="custom-select-wrapper">
                                <input type="hidden" id="newClientCollege" value="">
                                <div class="custom-select-display" id="collegeDisplay" onclick="toggleCustomSelect('collegeOptions')">Select College</div>
                                <div class="custom-select-options hidden-scrollbar" id="collegeOptions">
                                    <div class="custom-select-option" onclick="selectCustomOption('collegeOptions', 'newClientCollege', 'collegeDisplay', '', 'Select College')">Select College</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label class="form-label" style="margin-bottom: 0;">Department</label>
                                <a href="#" onclick="openManageAcademicModal('departments'); return false;" style="font-size: 12px; color: #3b82f6; text-decoration: none;">Manage</a>
                            </div>
                            <div class="custom-select-wrapper">
                                <input type="hidden" id="newClientDepartment" value="">
                                <div class="custom-select-display" id="deptDisplay" onclick="toggleCustomSelect('deptOptions')">Select Department</div>
                                <div class="custom-select-options hidden-scrollbar" id="deptOptions">
                                    <div class="custom-select-option" onclick="selectCustomOption('deptOptions', 'newClientDepartment', 'deptDisplay', '', 'Select Department')">Select Department</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="addClientSubmitBtn" class="btn-primary" style="width: 100%;">Save Record</button>
                </form>
            </div>
        </div>
    </div>


    <!-- Manage Academic Modal -->
    <div id="manageAcademicModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="manageAcademicTitle">Manage Records</h3>
                <button class="modal-close" onclick="closeManageAcademicModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addAcademicForm" onsubmit="addAcademicItem(event)" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="hidden" id="manageAcademicType">
                    <input type="text" id="newAcademicName" class="form-input" placeholder="Enter name to add..." required style="flex: 1;">
                    <button type="submit" class="btn-primary">Add</button>
                </form>
                <style>
                    .hidden-scrollbar::-webkit-scrollbar { display: none; }
                    .hidden-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                </style>
                <div class="table-responsive hidden-scrollbar" style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="position: sticky; top: 0; z-index: 1;">
                            <tr style="border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #64748b;">Name</th>
                                <th style="padding: 10px; text-align: right; font-size: 13px; color: #64748b; width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="manageAcademicList">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
