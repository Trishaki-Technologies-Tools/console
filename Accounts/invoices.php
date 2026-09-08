<?php
$page_title = "Invoices";
$current_page = "invoices";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="invoices-page">
                    <div class="section-header">
                        <div>
                            <h2>GST & Non-GST Invoices</h2>
                            <p class="company-subtitle">Manage client billings, invoice states and print layouts</p>
                        </div>
                        <div class="filter-row">

                            
                        </div>
                    </div>

                    <input type="file" id="csv-file-input" accept=".csv" style="display: none;"
                        onchange="importInvoicesCSV(event)">

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">🧾</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-amount">₹0.00</div>
                                <div class="stat-label-small">Total Amount Billed</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">💵</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-paid">₹0.00</div>
                                <div class="stat-label-small">Total Cumulative Paid</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-cyan">📋</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-count">0</div>
                                <div class="stat-label-small">Total Invoices Issued</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section" id="invoice-list-container">
                        <div class="records-header">
                            <h3>Issued Invoices</h3>
                            <div class="filter-row">
                                <input type="text" id="invoice-search" class="form-input"
                                    placeholder="Search by name/number..." onkeyup="searchInvoices()"
                                    style="max-width: 250px;">
                            </div>
                        </div>
                        <div class="table-responsive" id="invoice-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading invoices...</p>
                        </div>
                    </div>

                    <!-- Create Invoice Section (Hidden by default, displayed inline when clicking Generate) -->
                    <div class="records-section hidden" id="invoice-form-container">
                        <div class="records-header">
                            <h3 id="invoice-form-title">Create New Invoice</h3>
                            <button class="btn-secondary" onclick="closeInvoiceForm()">Back to List</button>
                        </div>
                        <form id="invoiceForm" onsubmit="saveInvoice(event)">
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Client / Billing Name <span
                                            class="required">*</span></label>
                                    <input type="text" id="billToName" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <input type="text" id="phone" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" id="email" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GST Number</label>
                                    <input type="text" id="gstNumber" class="form-input"
                                        placeholder="e.g. 29ABCDE1234F1Z5">
                                </div>
                            </div>
                            <div
                                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                
                                <div class="form-group">
                                    <label class="form-label">Invoice Date <span class="required">*</span></label>
                                    <input type="date" id="invoiceDate" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Type <span class="required">*</span></label>
                                    <select id="invoiceType" class="form-input" onchange="calculateInvoiceSummary()">
                                        <option value="non-gst">Non-GST</option>
                                        <option value="gst">GST (18%)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Invoice Number (Auto/Custom)</label>
                                    <input type="text" id="invoiceNo" class="form-input"
                                        placeholder="Keep empty for auto-generate">
                                </div>
                            </div>

                            <h4 style="margin-top: 30px; margin-bottom: 15px; color: #fff;">Invoice Items</h4>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Qty</th>
                                            <th>Rate (₹)</th>
                                            <th>GST Rate (%)</th>
                                            <th>Amount (₹)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoiceItemsTableBody">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 15px; margin-bottom: 30px;">
                                <button type="button" class="btn-secondary" onclick="addInvoiceItemRow()">+ Add
                                    Item</button>
                            </div>

                            <div
                                style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                                <div>
                                    <div class="form-group">
                                        <label class="form-label">Payment Received (₹)</label>
                                        <input type="number" id="amtPaid" class="form-input" value="0" step="0.01"
                                            oninput="calculateInvoiceSummary()">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Cumulative Total Paid (Read-Only)</label>
                                        <input type="number" id="cumulativePaid" class="form-input" value="0" readonly>
                                    </div>
                                </div>
                                <div
                                    style="text-align: right; color: var(--text-muted); font-size: 15px; font-weight: 500;">
                                    <p>Subtotal: <span id="invSubtotal"
                                            style="color: #fff; font-weight: 600;">₹0.00</span></p>
                                    <p id="invGstRow">CGST (9%) + SGST (9%): <span id="invGst"
                                            style="color: #fff; font-weight: 600;">₹0.00</span></p>
                                    <h3 style="font-size: 22px; margin-top: 10px; color: #fff;">Total Payable: <span
                                            id="invTotalPayable">₹0.00</span></h3>
                                    <h3 style="font-size: 18px; margin-top: 5px; color: var(--danger);">Balance Due:
                                        <span id="invBalanceDue">₹0.00</span>
                                    </h3>
                                </div>
                            </div>

                            <div class="modal-actions" style="margin-top: 30px; justify-content: flex-end;">
                                <button type="button" class="btn-cancel" onclick="closeInvoiceForm()">Cancel</button>
                                <button type="submit" class="btn-save">Save & Issue Invoice</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Voucher Content -->

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>