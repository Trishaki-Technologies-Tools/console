// Payment Receipts Module Functions
var allReceiptsList = [];
var currentReceiptTab = 'all';

// Load receipts from server
function loadReceipts() {
    const listContainer = document.getElementById('receipt-list');
    if (!listContainer) return;

    listContainer.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">Loading receipts...</p>';

    fetch('api/get_receipts.php')
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                allReceiptsList = data;
                renderReceiptsTable();
                updateReceiptStats();
            } else if (data.error) {
                listContainer.innerHTML = `<p style="padding: 40px; text-align: center; color: #ef4444;">Error: ${data.error}</p>`;
            }
        })
        .catch(err => {
            console.error('Error loading receipts:', err);
            listContainer.innerHTML = `<p style="padding: 40px; text-align: center; color: #ef4444;">Failed to load receipts.</p>`;
        });
}

// Filter tab switching
function switchReceiptTab(tab) {
    currentReceiptTab = tab;
    ['all', 'gst', 'nongst'].forEach(t => {
        const btn = document.getElementById(`tab-rec-${t}`);
        if (btn) {
            if (t === tab) {
                btn.classList.add('active');
                btn.style.background = '#6366f1';
                btn.style.color = '#fff';
            } else {
                btn.classList.remove('active');
                btn.style.background = 'transparent';
                btn.style.color = '#94a3b8';
            }
        }
    });
    renderReceiptsTable();
}

// Search Receipts
function searchReceipts() {
    renderReceiptsTable();
}

// Render Table
function renderReceiptsTable() {
    const listContainer = document.getElementById('receipt-list');
    if (!listContainer) return;

    const query = (document.getElementById('receipt-search')?.value || '').toLowerCase().trim();

    let filtered = allReceiptsList.filter(rec => {
        // Tab filter
        if (currentReceiptTab === 'gst' && (rec.type || '').toLowerCase() !== 'gst') return false;
        if (currentReceiptTab === 'nongst' && (rec.type || '').toLowerCase() !== 'non-gst') return false;

        // Search query
        if (query) {
            const receiptNo = (rec.receipt_no || '').toLowerCase();
            const clientName = (rec.billToName || rec.name || '').toLowerCase();
            const phone = (rec.phone || '').toLowerCase();
            const invoiceNo = (rec.invoice_no || '').toLowerCase();

            return receiptNo.includes(query) || clientName.includes(query) || phone.includes(query) || invoiceNo.includes(query);
        }
        return true;
    });

    if (filtered.length === 0) {
        listContainer.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No payment receipts found.</p>';
        return;
    }

    let html = `<table>
        <thead>
            <tr>
                <th>Receipt No</th>
                <th>Invoice No</th>
                <th>Client Name</th>
                <th>Phone</th>
                <th>Type</th>
                <th>Paid Amount</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;

    filtered.forEach(rec => {
        let items = [];
        try {
            items = typeof rec.items === 'string' ? JSON.parse(rec.items) : rec.items;
        } catch(e) {}

        let paidAmount = 0;
        if (typeof rec.paidAmount !== 'undefined' && rec.paidAmount > 0) {
            paidAmount = parseFloat(rec.paidAmount);
        } else if (Array.isArray(items) && items.length > 0) {
            items.forEach(itm => {
                paidAmount += parseFloat(itm.paidAmt ?? itm.amount ?? itm.totalInclTax ?? 0);
            });
        }
        if (paidAmount <= 0) {
            paidAmount = parseFloat(rec.cumulative_total_paid || rec.cumulativeTotalPaid || 0);
        }

        const formattedDate = (rec.receipt_date || rec.date) ? new Date(rec.receipt_date || rec.date).toLocaleDateString('en-GB') : 'N/A';
        const receiptNoStr = rec.receipt_no || rec.receiptNo || 'N/A';
        const invoiceNoStr = rec.invoice_no || rec.invoiceNo || '';
        const clientNameStr = rec.billToName || rec.client_name || rec.name || 'N/A';
        const invDisplay = invoiceNoStr 
            ? `<strong>${invoiceNoStr}</strong>` 
            : `<span style="background: rgba(245, 158, 11, 0.14); color: #d97706; padding: 3px 6px; border-radius: 4px; font-weight: 700; font-size: 11px; border: 1px dashed rgba(245, 158, 11, 0.5); display: inline-block;">Unallocated</span>`;

        html += `<tr>
            <td><strong>${receiptNoStr}</strong></td>
            <td>${invDisplay}</td>
            <td>${clientNameStr}</td>
            <td>${rec.phone || 'N/A'}</td>
            <td><span class="category-badge">${(rec.type || '').toUpperCase() === 'GST' ? 'GST' : 'Non-GST'}</span></td>
            <td><strong>₹${paidAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong></td>
            <td>${formattedDate}</td>
            <td>
                <div style="display: flex; gap: 6px; flex-wrap: nowrap; align-items: center;">
                    <button class="btn-action" onclick="viewReceipt('${receiptNoStr}', '${rec.token || ''}')" title="View / Print" style="background: #0ea5e9; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                    <button class="btn-action" onclick="copyReceiptLink('${receiptNoStr}', '${rec.token || ''}')" title="Copy Shareable Link" style="background: #a855f7; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    </button>
                    <button class="btn-action" onclick="downloadReceiptPDF('${receiptNoStr}')" title="Download PDF" style="background: #10b981; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </button>
                    <button class="btn-action" onclick="editReceipt('${receiptNoStr}')" title="Edit Receipt" style="background: #64748b; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="btn-action" onclick="deleteReceipt('${receiptNoStr}')" title="Delete Receipt" style="background: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    });

    html += `</tbody></table>`;
    listContainer.innerHTML = html;
}

// Update stats cards
function updateReceiptStats() {
    let totalPaid = 0;
    let totalBilled = 0;
    let gstPaid = 0;
    let gstCount = 0;

    allReceiptsList.forEach(rec => {
        let paid = 0;
        if (typeof rec.paidAmount !== 'undefined' && rec.paidAmount > 0) {
            paid = parseFloat(rec.paidAmount);
        } else {
            try {
                const items = typeof rec.items === 'string' ? JSON.parse(rec.items) : rec.items;
                if (Array.isArray(items)) {
                    items.forEach(itm => {
                        paid += parseFloat(itm.paidAmt ?? itm.amount ?? itm.totalInclTax ?? 0);
                    });
                }
            } catch(e) {}
        }
        if (paid <= 0) paid = parseFloat(rec.cumulative_total_paid || 0);
        const billed = parseFloat(rec.original_total_payable || 0);

        totalPaid += paid;
        totalBilled += billed;

        if ((rec.type || '').toLowerCase() === 'gst') {
            gstPaid += paid;
            gstCount++;
        }
    });

    if (document.getElementById('total-receipt-paid')) {
        document.getElementById('total-receipt-paid').innerText = '₹' + totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    if (document.getElementById('total-receipt-count-sub')) {
        document.getElementById('total-receipt-count-sub').innerText = `${allReceiptsList.length} Receipts Issued`;
    }

    if (document.getElementById('total-receipt-billed')) {
        document.getElementById('total-receipt-billed').innerText = '₹' + totalBilled.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    if (document.getElementById('gst-receipt-paid')) {
        document.getElementById('gst-receipt-paid').innerText = '₹' + gstPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    if (document.getElementById('gst-receipt-count')) {
        document.getElementById('gst-receipt-count').innerText = `${gstCount} GST Receipts`;
    }
}

// Form Handlers
function openCreateReceiptModal() {
    document.getElementById('receipt-list-container').classList.add('hidden');
    document.getElementById('receipt-form-container').classList.remove('hidden');
    document.getElementById('receipt-form-title').innerText = 'Create New Receipt';
    
    // Reset Form
    document.getElementById('receiptForm').reset();
    document.getElementById('receiptEditId').value = '';
    document.getElementById('receiptDate').value = new Date().toISOString().split('T')[0];
    
    // Clear items table
    const tbody = document.getElementById('receiptItemsTableBody');
    tbody.innerHTML = '';
    addReceiptItemRow();
}

function closeReceiptForm() {
    document.getElementById('receipt-form-container').classList.add('hidden');
    document.getElementById('receipt-list-container').classList.remove('hidden');
}

function addReceiptItemRow(description = 'Payment Received', mode = 'Cash', date = '', amount = 0) {
    const tbody = document.getElementById('receiptItemsTableBody');
    const itemDate = date || document.getElementById('receiptDate')?.value || new Date().toISOString().split('T')[0];

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-input rec-item-desc" value="${description}" required></td>
        <td>
            <select class="form-input rec-item-mode"></select>
        </td>
        <td><input type="date" class="form-input rec-item-date" value="${itemDate}" required></td>
        <td><input type="number" class="form-input rec-item-amount" value="${amount}" step="0.01" min="0" oninput="calculateReceiptTotals()" required></td>
        <td><button type="button" class="btn-action" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px;" onclick="removeReceiptItemRow(this)">−</button></td>
    `;
    tbody.appendChild(tr);

    const modeSelect = tr.querySelector('.rec-item-mode');
    if (typeof populatePaymentSelectWithOptions === 'function') {
        populatePaymentSelectWithOptions(modeSelect, mode);
    } else if (typeof fetchAppPaymentModes === 'function') {
        fetchAppPaymentModes().then(() => {
            if (typeof populatePaymentSelectWithOptions === 'function') {
                populatePaymentSelectWithOptions(modeSelect, mode);
            }
        });
    }

    calculateReceiptTotals();
}

function removeReceiptItemRow(btn) {
    const tbody = document.getElementById('receiptItemsTableBody');
    if (tbody.children.length > 1) {
        btn.closest('tr').remove();
        calculateReceiptTotals();
    } else {
        alert('At least one item row is required.');
    }
}

function calculateReceiptTotals() {
    const amountInputs = document.querySelectorAll('.rec-item-amount');
    let paidNow = 0;
    amountInputs.forEach(input => {
        paidNow += parseFloat(input.value || 0);
    });

    const origInput = document.getElementById('receiptOriginalTotal');
    let origTotal = parseFloat(origInput?.value || 0);
    if (origTotal <= 0 && paidNow > 0) {
        origTotal = paidNow;
        if (origInput) origInput.value = origTotal;
    }

    const cumInput = document.getElementById('receiptCumulativePaid');
    let cumPaid = parseFloat(cumInput?.value || 0);
    if (cumPaid <= 0 && paidNow > 0) {
        cumPaid = paidNow;
        if (cumInput) cumInput.value = cumPaid;
    }

    const due = Math.max(0, origTotal - cumPaid);

    if (document.getElementById('recPaidNowText')) {
        document.getElementById('recPaidNowText').innerText = '₹' + paidNow.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    if (document.getElementById('recBalanceDueText')) {
        document.getElementById('recBalanceDueText').innerText = '₹' + due.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
}

function saveReceipt(e) {
    e.preventDefault();

    const editId = document.getElementById('receiptEditId').value;
    const name = document.getElementById('receiptBillToName').value.trim();
    const rawPhone = document.getElementById('receiptPhone').value.trim();
    const phone = rawPhone.replace(/[^0-9]/g, '');
    if (phone.length !== 10) {
        alert('Please enter a valid 10-digit phone number.');
        return;
    }
    const email = document.getElementById('receiptEmail').value.trim();
    const gstNumber = document.getElementById('receiptGstNumber').value.trim();
    const receiptDate = document.getElementById('receiptDate').value;
    const type = document.getElementById('receiptType').value;
    const invoiceNo = document.getElementById('receiptInvoiceNo').value.trim();
    const receiptNo = document.getElementById('receiptNo').value.trim();

    const itemRows = document.querySelectorAll('#receiptItemsTableBody tr');
    const items = [];
    itemRows.forEach(tr => {
        items.push({
            description: tr.querySelector('.rec-item-desc').value.trim(),
            paymentMode: tr.querySelector('.rec-item-mode').value,
            date: tr.querySelector('.rec-item-date').value,
            amount: parseFloat(tr.querySelector('.rec-item-amount').value || 0),
            paidAmt: parseFloat(tr.querySelector('.rec-item-amount').value || 0)
        });
    });

    const originalTotalPayable = parseFloat(document.getElementById('receiptOriginalTotal').value || 0);
    const cumulativeTotalPaid = parseFloat(document.getElementById('receiptCumulativePaid').value || 0);

    const payload = {
        editId: editId,
        type: type,
        billToName: name,
        phone: phone,
        email: email,
        gstNumber: gstNumber,
        receiptDate: receiptDate,
        invoice_no: invoiceNo,
        receiptNo: receiptNo,
        items: items,
        originalTotalPayable: originalTotalPayable,
        cumulativeTotalPaid: cumulativeTotalPaid
    };

    fetch('api/save_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Receipt saved successfully!');
            closeReceiptForm();
            loadReceipts();
        } else {
            alert('Error: ' + (data.error || 'Failed to save receipt.'));
        }
    })
    .catch(err => {
        console.error('Save receipt error:', err);
        alert('Network error saving receipt.');
    });
}

function viewReceipt(receiptNo, token) {
    if (token) {
        window.open(`api/view_receipt.php?token=${token}`, '_blank');
    } else {
        window.open(`api/view_receipt.php?receiptNo=${encodeURIComponent(receiptNo)}`, '_blank');
    }
}

function copyReceiptLink(receiptNo, token) {
    let url = window.location.origin + window.location.pathname.replace('receipts.php', '') + `api/view_receipt.php?receiptNo=${encodeURIComponent(receiptNo)}`;
    if (token) {
        url = window.location.origin + window.location.pathname.replace('receipts.php', '') + `api/view_receipt.php?token=${token}`;
    }
    navigator.clipboard.writeText(url).then(() => {
        alert('Receipt share link copied to clipboard!');
    });
}

function downloadReceiptPDF(receiptNo) {
    window.open(`api/download_receipt_pdf.php?receiptNo=${encodeURIComponent(receiptNo)}`, '_blank');
}

function editReceipt(receiptNo) {
    const rec = allReceiptsList.find(r => r.receipt_no === receiptNo);
    if (!rec) return;

    openCreateReceiptModal();
    document.getElementById('receipt-form-title').innerText = 'Edit Receipt ' + rec.receipt_no;
    document.getElementById('receiptEditId').value = rec.id || '';

    document.getElementById('receiptBillToName').value = rec.billToName || rec.client_name || '';
    document.getElementById('receiptPhone').value = rec.phone || '';
    document.getElementById('receiptEmail').value = rec.email || '';
    document.getElementById('receiptGstNumber').value = rec.gstNumber || rec.gst_number || '';
    document.getElementById('receiptDate').value = rec.receipt_date || new Date().toISOString().split('T')[0];
    document.getElementById('receiptType').value = (rec.type || 'non-gst').toLowerCase();
    document.getElementById('receiptInvoiceNo').value = rec.invoice_no || '';
    document.getElementById('receiptNo').value = rec.receipt_no || '';

    document.getElementById('receiptOriginalTotal').value = rec.original_total_payable || 0;
    document.getElementById('receiptCumulativePaid').value = rec.cumulative_total_paid || 0;

    let items = [];
    try {
        items = typeof rec.items === 'string' ? JSON.parse(rec.items) : rec.items;
    } catch(e) {}

    const tbody = document.getElementById('receiptItemsTableBody');
    tbody.innerHTML = '';

    if (Array.isArray(items) && items.length > 0) {
        items.forEach(item => {
            addReceiptItemRow(
                item.description || 'Payment Received',
                item.paymentMode || 'Cash',
                item.date || rec.receipt_date,
                item.paidAmt || item.amount || 0
            );
        });
    } else {
        addReceiptItemRow();
    }
}

function deleteReceipt(receiptNo) {
    if (!confirm(`Are you sure you want to delete receipt ${receiptNo}?`)) return;

    fetch('api/delete_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiptNo: receiptNo, receipt_no: receiptNo })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Receipt deleted successfully.');
            loadReceipts();
        } else {
            alert('Error deleting receipt: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Delete error:', err);
        alert('Network error deleting receipt.');
    });
}

function masterPrintReceipts() {
    window.open('api/master_print_receipts.php', '_blank');
}

function openBulkReceiptModal() {
    document.getElementById('receipt-csv-file-input').click();
}

function importReceiptsCSV(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('csvFile', file);

    fetch('api/bulk_receipt_process.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`Bulk imported ${data.count} receipts successfully!`);
            loadReceipts();
        } else {
            alert('Bulk import error: ' + (data.error || 'Failed to process CSV.'));
        }
    })
    .catch(err => {
        console.error('CSV Import error:', err);
        alert('Network error uploading CSV.');
    });
}

// Auto load if on receipts page
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('receipt-list')) {
        loadReceipts();
    }
    const urlParams = new URLSearchParams(window.location.search);
    const payInvoiceNo = urlParams.get('pay_invoice');
    if (payInvoiceNo) {
        fetch('api/get_invoices.php')
            .then(res => res.json())
            .then(invoices => {
                if (Array.isArray(invoices)) {
                    window.generatedInvoices = invoices;
                    if (typeof payInvoiceByNo === 'function') {
                        payInvoiceByNo(payInvoiceNo);
                    }
                }
            })
            .catch(err => console.error('Error auto-loading pay invoice:', err));
    }
});
