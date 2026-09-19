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

                    </div>

                    <input type="file" id="csv-file-input" accept=".csv" style="display: none;"
                        onchange="importInvoicesCSV(event)">

                    <div class="stats-row-small">
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-blue">🧾</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="total-invoice-amount">₹0.00</div>
                                <div class="stat-label-small">Total Amount Billed</div>
                                <div class="stat-sublabel-small" id="total-invoice-count-sub" style="font-size: 11px; color: #94a3b8; margin-top: 2px;">0 Invoices</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-cyan">🏛️</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="gst-invoice-amount">₹0.00</div>
                                <div class="stat-label-small">GST Amount Billed</div>
                                <div class="stat-sublabel-small" id="gst-invoice-count" style="font-size: 11px; color: #38bdf8; margin-top: 2px;">0 GST Invoices</div>
                            </div>
                        </div>
                        <div class="stat-card-small">
                            <div class="stat-icon-small icon-green">💵</div>
                            <div class="stat-info-small">
                                <div class="stat-value-small" id="nongst-invoice-amount">₹0.00</div>
                                <div class="stat-label-small">Non-GST Amount Billed</div>
                                <div class="stat-sublabel-small" id="nongst-invoice-count" style="font-size: 11px; color: #4ade80; margin-top: 2px;">0 Non-GST Invoices</div>
                            </div>
                        </div>
                    </div>

                    <div class="records-section" id="invoice-list-container">
                        <div class="records-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <h3 style="margin: 0;">Issued Invoices</h3>
                                <div class="invoice-tab-switch" style="display: inline-flex; background: rgba(255,255,255,0.06); padding: 4px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12);">
                                    <button type="button" class="tab-btn active" id="tab-inv-all" onclick="switchInvoiceTab('all')" style="padding: 6px 16px; border-radius: 6px; border: none; background: #6366f1; color: #fff; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s ease;">All Bills</button>
                                    <button type="button" class="tab-btn" id="tab-inv-gst" onclick="switchInvoiceTab('gst')" style="padding: 6px 16px; border-radius: 6px; border: none; background: transparent; color: #94a3b8; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s ease;">GST Bills</button>
                                    <button type="button" class="tab-btn" id="tab-inv-nongst" onclick="switchInvoiceTab('nongst')" style="padding: 6px 16px; border-radius: 6px; border: none; background: transparent; color: #94a3b8; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s ease;">Non-GST Bills</button>
                                </div>
                            </div>
                            <div class="filter-row" style="display: flex; gap: 10px; align-items: center;">
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

<!-- Edit Invoice & Manage Linked Receipts Modal -->
<div id="editInvoiceModal" class="modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 9999;">
    <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; width: 92%; max-width: 900px; max-height: 90vh; overflow-y: auto; padding: 28px; color: #0f172a; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 22px;">
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a;" id="editInvModalTitle">Edit Invoice Details</h3>
            <button type="button" onclick="closeEditInvoiceModal()" style="background: #f1f5f9; border: none; color: #64748b; font-size: 20px; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer;">&times;</button>
        </div>

        <!-- Top Section: Invoice Header Details -->
        <form id="editInvoiceForm" onsubmit="submitEditInvoiceHeader(event)">
            <input type="hidden" id="editInvId" value="">
            <input type="hidden" id="editInvOriginalNo" value="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Client / Student Name <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="editInvBillToName" class="form-input" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #64748b; font-size: 14px; cursor: not-allowed;" readonly required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Phone Number <span style="color:#ef4444;">*</span></label>
                    <input type="text" id="editInvPhone" class="form-input" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #64748b; font-size: 14px; cursor: not-allowed;" readonly required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Email Address</label>
                    <input type="email" id="editInvEmail" class="form-input" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #64748b; font-size: 14px; cursor: not-allowed;" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">GST Number</label>
                    <input type="text" id="editInvGstNumber" class="form-input" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #64748b; font-size: 14px; cursor: not-allowed;" readonly>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 22px;">
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Invoice Date <span style="color:#ef4444;">*</span></label>
                    <input type="date" id="editInvDate" class="form-input" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #0f172a; font-size: 14px;" required>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Invoice Type <span style="color:#ef4444;">*</span></label>
                    <select id="editInvType" class="form-input" style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #64748b; font-size: 14px; cursor: not-allowed;" disabled>
                        <option value="non-gst">Non-GST</option>
                        <option value="gst">GST (18%)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; display: block;">Original Total Payable (₹) <span style="color:#ef4444;">*</span></label>
                    <input type="number" id="editInvTotalPayable" class="form-input" step="0.01" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #0f172a; font-size: 14px; font-weight: 600;" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-bottom: 25px;">
                <button type="submit" class="btn-primary" style="padding: 10px 22px; font-size: 14px; background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border-radius: 10px; border: none; color: #fff; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);">Save Invoice Header Info</button>
            </div>
        </form>

        <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 25px 0;">

        <!-- Bottom Section: Linked Receipts Table -->
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #4f46e5;">Linked Payment Receipts for Invoice</h4>
                <span id="editInvSummaryText" style="font-size: 13px; color: #64748b; font-weight: 500;"></span>
            </div>
            <div class="table-responsive" id="editInvReceiptsList">
                <p style="text-align: center; color: #64748b; padding: 20px;">Loading receipts...</p>
            </div>
        </div>
    </div>
</div>

<!-- Edit Receipt Payment Details Nested Modal -->
<div id="editReceiptItemModal" class="modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(8px); display: none; justify-content: center; align-items: center; z-index: 10000;">
    <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; width: 90%; max-width: 480px; padding: 28px; color: #0f172a; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 22px;">
            <h4 style="margin: 0; font-size: 18px; font-weight: 700; color: #0f172a;" id="editRecModalTitle">Edit Receipt Payment</h4>
            <button type="button" onclick="closeEditReceiptItemModal()" style="background: #f1f5f9; border: none; color: #64748b; font-size: 20px; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer;">&times;</button>
        </div>
        <form id="editReceiptItemForm" onsubmit="submitEditReceiptItem(event)">
            <input type="hidden" id="editRecId" value="">
            <input type="hidden" id="editRecNo" value="">
            <input type="hidden" id="editRecInvoiceNo" value="">

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Payment Date</label>
                <input type="date" id="editRecDate" class="form-input" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #0f172a; font-size: 14px;" required>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Payment Mode</label>
                <select id="editRecMode" class="form-input" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #0f172a; font-size: 14px;" required></select>
            </div>
            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Amount Paid (₹)</label>
                <input type="number" id="editRecAmount" class="form-input" step="0.01" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; color: #059669; font-weight: 700; font-size: 16px;" required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeEditReceiptItemModal()" style="padding: 10px 18px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; color: #475569; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 10px 20px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; border-radius: 10px; color: #fff; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">Update Receipt</button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Pay Invoice Modal -->
<div id="payInvoiceModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(8px); z-index: 9999; justify-content: center; align-items: center;">
    <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; width: 92%; max-width: 480px; padding: 28px; color: #0f172a; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0; font-size: 19px; font-weight: 700; color: #0f172a;">Record Invoice Payment</h3>
                <span id="payModalInvoiceNo" style="font-size: 12px; background: #eff6ff; color: #2563eb; font-weight: 600; padding: 3px 10px; border-radius: 6px; border: 1px solid #dbeafe; display: inline-block; margin-top: 4px;"></span>
            </div>
            <button type="button" onclick="closePayInvoiceModal()" style="background: #f1f5f9; border: none; color: #64748b; font-size: 20px; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; cursor: pointer;">&times;</button>
        </div>

        <form id="payInvoiceForm" onsubmit="submitQuickPayment(event)">
            <input type="hidden" id="payInvId" value="">
            <input type="hidden" id="payInvNo" value="">
            <input type="hidden" id="payInvBillToName" value="">
            <input type="hidden" id="payInvPhone" value="">
            <input type="hidden" id="payInvEmail" value="">
            <input type="hidden" id="payInvGstNumber" value="">
            <input type="hidden" id="payInvType" value="">
            <input type="hidden" id="payInvOriginalTotal" value="">
            <input type="hidden" id="payInvCumulativePaid" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; margin-bottom: 22px; font-size: 13.5px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 500;">Client / Student:</span>
                    <span id="payModalClientName" style="font-weight: 600; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="color: #64748b; font-weight: 500;">Total Invoice Amount:</span>
                    <span id="payModalTotalAmount" style="font-weight: 600; color: #0f172a;"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b; font-weight: 500;">Remaining Pending:</span>
                    <span id="payModalPendingAmount" style="font-weight: 700; color: #dc2626;"></span>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Payment Method / Mode <span style="color:#ef4444;">*</span></label>
                <select id="payMode" class="form-input" style="width: 100%; padding: 11px 14px; border-radius: 10px; background: #ffffff; border: 1px solid #cbd5e1; color: #0f172a; font-size: 14px; font-weight: 500;" required></select>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Payment Date <span style="color:#ef4444;">*</span></label>
                <input type="date" id="payDate" class="form-input" style="width: 100%; padding: 11px 14px; border-radius: 10px; background: #ffffff; border: 1px solid #cbd5e1; color: #0f172a; font-size: 14px; font-weight: 500;" required>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Amount Paying (₹) <span style="color:#ef4444;">*</span></label>
                <input type="number" id="payAmount" class="form-input" step="0.01" min="0.01" style="width: 100%; padding: 11px 14px; border-radius: 10px; background: #ffffff; border: 1px solid #cbd5e1; color: #0284c7; font-weight: 700; font-size: 16px;" required>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closePayInvoiceModal()" style="padding: 10px 18px; font-size: 13.5px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 10px; color: #475569; font-weight: 600; cursor: pointer;">Cancel</button>
                <button type="submit" style="padding: 11px 22px; font-size: 14px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border-radius: 10px; border: none; color: #fff; font-weight: 600; cursor: pointer; box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);">Generate Payment Receipt</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>