<?php
$page_title = "Payment Receipts";
$current_page = "receipts";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="receipts-page">
                    <div class="section-header">
                        <div>
                            <h2>Payment Receipts</h2>
                            <p class="company-subtitle">Track, generate, and manage payment receipts and partial payment continuations</p>
                        </div>
                    </div>

                    <input type="file" id="receipt-csv-file-input" accept=".csv" style="display: none;" onchange="importReceiptsCSV(event)">

                    <div class="records-section" id="receipt-list-container">
                        <div class="records-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <h3 style="margin: 0;">Issued Receipts</h3>
                                <div class="invoice-tab-switch" style="display: inline-flex; background: rgba(255,255,255,0.06); padding: 4px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.12);">
                                    <button type="button" class="tab-btn active" id="tab-rec-all" onclick="switchReceiptTab('all')" style="padding: 6px 16px; border-radius: 6px; border: none; background: #6366f1; color: #fff; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s ease;">All Receipts</button>
                                    <button type="button" class="tab-btn" id="tab-rec-gst" onclick="switchReceiptTab('gst')" style="padding: 6px 16px; border-radius: 6px; border: none; background: transparent; color: #94a3b8; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s ease;">GST Receipts</button>
                                    <button type="button" class="tab-btn" id="tab-rec-nongst" onclick="switchReceiptTab('nongst')" style="padding: 6px 16px; border-radius: 6px; border: none; background: transparent; color: #94a3b8; font-weight: 600; cursor: pointer; font-size: 13px; transition: all 0.2s ease;">Non-GST Receipts</button>
                                </div>
                            </div>
                            <div class="filter-row">
                                <input type="text" id="receipt-search" class="form-input" placeholder="Search by name/number/invoice..." onkeyup="searchReceipts()" style="max-width: 250px;">
                            </div>
                        </div>
                        <div class="table-responsive" id="receipt-list">
                            <p style="padding: 40px; text-align: center; color: #64748b;">Loading payment receipts...</p>
                        </div>
                    </div>

                    <!-- Create/Edit Receipt Section -->
                    <div class="records-section hidden" id="receipt-form-container">
                        <div class="records-header">
                            <h3 id="receipt-form-title">Create New Receipt</h3>
                            <button class="btn-secondary" onclick="closeReceiptForm()">Back to List</button>
                        </div>
                        <form id="receiptForm" onsubmit="saveReceipt(event)">
                            <input type="hidden" id="receiptEditId" value="">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Client / Billing Name <span class="required">*</span></label>
                                    <input type="text" id="receiptBillToName" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="required">*</span></label>
                                    <input type="tel" id="receiptPhone" class="form-input" placeholder="Enter 10-digit phone number" required
                                        pattern="[0-9]{10}" maxlength="10" minlength="10"
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" id="receiptEmail" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">GST Number</label>
                                    <input type="text" id="receiptGstNumber" class="form-input" placeholder="e.g. 29ABCDE1234F1Z5">
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div class="form-group">
                                    <label class="form-label">Receipt Date <span class="required">*</span></label>
                                    <input type="date" id="receiptDate" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Receipt Type <span class="required">*</span></label>
                                    <select id="receiptType" class="form-input">
                                        <option value="non-gst">Non-GST</option>
                                        <option value="gst">GST (18%)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Linked Invoice No (Optional)</label>
                                    <input type="text" id="receiptInvoiceNo" class="form-input" placeholder="e.g. TSK-2026-001">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Receipt Number (Auto/Custom)</label>
                                    <input type="text" id="receiptNo" class="form-input" placeholder="Keep empty for auto-generate">
                                </div>
                            </div>

                            <h4 style="margin-top: 30px; margin-bottom: 15px; color: #fff;">Receipt Items</h4>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Payment Mode</th>
                                            <th>Date</th>
                                            <th>Amount (₹)</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="receiptItemsTableBody">
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div style="margin-top: 15px; margin-bottom: 30px;">
                                <button type="button" class="btn-secondary" onclick="addReceiptItemRow()">+ Add Item Row</button>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; border-top: 1px solid var(--border-light); padding-top: 20px;">
                                <div>
                                    <div class="form-group">
                                        <label class="form-label">Original Total Payable (₹)</label>
                                        <input type="number" id="receiptOriginalTotal" class="form-input" value="0" step="0.01" oninput="calculateReceiptTotals()">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Cumulative Paid Till Date (₹)</label>
                                        <input type="number" id="receiptCumulativePaid" class="form-input" value="0" step="0.01" oninput="calculateReceiptTotals()">
                                    </div>
                                </div>
                                <div style="text-align: right; color: var(--text-muted); font-size: 15px; font-weight: 500;">
                                    <h3 style="font-size: 20px; margin-top: 10px; color: #059669;">Current Payment Received: <span id="recPaidNowText">₹0.00</span></h3>
                                    <h3 style="font-size: 18px; margin-top: 5px; color: var(--danger);">Balance Due: <span id="recBalanceDueText">₹0.00</span></h3>
                                </div>
                            </div>

                            <div class="modal-actions" style="margin-top: 30px; justify-content: flex-end;">
                                <button type="button" class="btn-cancel" onclick="closeReceiptForm()">Cancel</button>
                                <button type="submit" class="btn-save">Save & Issue Receipt</button>
                            </div>
                        </form>
                    </div>
                </div>

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>
