// Invoice Management System
var generatedInvoices = [];
var userDatabase = [];

function loadInvoices() {
    const container = document.getElementById('invoice-list');
    if (!container) return;

    fetch('api/get_invoices.php')
        .then(response => response.json())
        .then(data => {
            if (Array.isArray(data)) {
                generatedInvoices = data;
                displayInvoices();
            } else if (data && data.error) {
                container.innerHTML = `<p style="padding: 40px; text-align: center; color: #ef4444;">Error: ${data.error}</p>`;
            }
        })
        .catch(err => {
            console.error('Error loading invoices:', err);
            container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 40px;">Failed to load invoices.</p>';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('invoice-list')) {
        loadInvoices();
    }
});


// Initialize: Set today's date for initial invoice items
function setInitialDates() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('.gst-date, #nonGstItemsContainer input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
}

var currentInvoiceTab = 'all';

function switchInvoiceTab(tab) {
    currentInvoiceTab = tab;
    ['all', 'gst', 'nongst'].forEach(t => {
        const btn = document.getElementById('tab-inv-' + t);
        if (btn) {
            if (t === tab) {
                btn.style.background = '#6366f1';
                btn.style.color = '#ffffff';
                btn.classList.add('active');
            } else {
                btn.style.background = 'transparent';
                btn.style.color = '#94a3b8';
                btn.classList.remove('active');
            }
        }
    });
    displayInvoices();
}

function searchInvoices() {
    displayInvoices();
}

// Display invoices
function displayInvoices() {
    const container = document.getElementById('invoice-list');
    if (!container) return;
    
    if (!generatedInvoices || generatedInvoices.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No invoices generated yet.</p>';
        return;
    }

    const searchInput = document.getElementById('invoice-search');
    const searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
    
    // Filter by Tab (GST vs Non-GST vs All)
    let filteredInvoices = generatedInvoices.filter(inv => {
        const invType = (inv.type || '').toLowerCase();
        if (currentInvoiceTab === 'gst') {
            return invType === 'gst';
        } else if (currentInvoiceTab === 'nongst') {
            return invType === 'non-gst' || invType === 'nongst' || invType !== 'gst';
        }
        return true; // 'all'
    });
    
    // Filter by Search Query
    if (searchVal) {
        filteredInvoices = filteredInvoices.filter(inv => {
            const no = (inv.invoiceNo || '').toLowerCase();
            const name = (inv.billToName || '').toLowerCase();
            const phone = (inv.phone || '').toLowerCase();
            return no.includes(searchVal) || name.includes(searchVal) || phone.includes(searchVal);
        });
    }

    if (filteredInvoices.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No matching invoices found.</p>';
        return;
    }
    
    let html = `<table>
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Type</th>
                <th>Client Name / Phone</th>
                <th>Invoice Date</th>
                <th>Last Receipt / Paid</th>
                <th>Total / Pending</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;
    
    const sortedInvoices = [...filteredInvoices].sort((a, b) => {
        const dateA = a.date || a.invoice_date || a.generatedAt;
        const dateB = b.date || b.invoice_date || b.generatedAt;
        return new Date(dateB) - new Date(dateA);
    });

    sortedInvoices.forEach((invoice) => {
        const isAllocated = !!(invoice.invoiceNo && invoice.invoiceNo.trim() !== '');
        const rawDate = isAllocated ? (invoice.date || invoice.invoice_date) : null;
        const formattedDate = rawDate ? new Date(rawDate).toLocaleDateString('en-GB') : '-';
        const isGst = (invoice.type || '').toLowerCase() === 'gst';
        const badgeLabel = isGst ? 'GST' : 'Non-GST';
        const badgeBg = isGst ? '#0284c7' : '#64748b';

        const origTotal = parseFloat(invoice.originalTotalPayable || 0);
        const cumPaid = parseFloat(invoice.cumulativeTotalPaid || 0);
        const pendingAmt = Math.max(0, origTotal - cumPaid);
        const lastPaid = parseFloat(invoice.lastAmountPaid || 0);
        const lastRecNo = invoice.lastReceiptNo || '-';

        const isPaid = pendingAmt <= 0.01;
        const statusBadge = isPaid
            ? '<span class="status-badge paid" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px;">Paid</span>'
            : '<span class="status-badge partial" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 12px;">Pending</span>';

        const invDisplayNo = isAllocated
            ? `<strong>${invoice.invoiceNo}</strong>`
            : `<span style="background: rgba(245, 158, 11, 0.14); color: #d97706; padding: 4px 8px; border-radius: 6px; font-weight: 700; font-size: 11.5px; border: 1px dashed rgba(245, 158, 11, 0.5); display: inline-block;">Unallocated</span>`;

        html += `<tr>
            <td>${invDisplayNo}</td>
            <td><span class="category-badge" style="background: ${badgeBg}; color: #ffffff; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">${badgeLabel}</span></td>
            <td>
                <div><strong>${invoice.billToName || 'N/A'}</strong></div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">${invoice.phone || 'N/A'}</div>
            </td>
            <td>${formattedDate}</td>
            <td>
                <div><strong>${lastRecNo}</strong></div>
                <div style="font-size: 12px; color: #10b981; margin-top: 2px;">${lastPaid > 0 ? '₹' + lastPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '-'}</div>
            </td>
            <td>
                <div><strong>₹${origTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</strong></div>
                <div style="font-size: 12px; color: ${pendingAmt > 0 ? '#ef4444' : '#10b981'}; margin-top: 2px;">Pending: ₹${pendingAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</div>
            </td>
            <td>${statusBadge}</td>
            <td>
                <div style="display: flex; gap: 6px; flex-wrap: nowrap; align-items: center;">
                    ${!isPaid ? `
                        <button class="btn-action" onclick="payInvoiceById(${invoice.id})" title="Pay Remaining Balance" style="background: #10b981; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 4px; font-weight: 600; font-size: 12px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg> Pay
                        </button>
                    ` : ''}
                    <button class="btn-action" onclick="viewInvoiceById(${invoice.id}, '${invoice.invoiceNo || ''}', '${invoice.type}')" title="View Bill / Invoice" style="background: #0ea5e9; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </button>
                    <button class="btn-action" onclick="editInvoiceById(${invoice.id})" title="Edit Data" style="background: #64748b; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="btn-action" onclick="deleteInvoiceById(${invoice.id}, '${invoice.invoiceNo || ''}', '${invoice.type}')" title="Delete Bill / Invoice" style="background: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;

    // Update Stats Cards
    const totalCount = generatedInvoices.length;
    
    // Group by student phone to avoid double-counting installments (P1, P2)
    const studentLatest = {};
    const gstStudentLatest = {};
    const nonGstStudentLatest = {};
    
    let gstCount = 0;
    let nonGstCount = 0;

    generatedInvoices.forEach(inv => {
        const phone = inv.phone || inv.billToName;
        const isGst = (inv.type || '').toLowerCase() === 'gst';

        if (!studentLatest[phone] || parseInt(inv.id) > parseInt(studentLatest[phone].id)) {
            studentLatest[phone] = inv;
        }

        if (isGst) {
            gstCount++;
            if (!gstStudentLatest[phone] || parseInt(inv.id) > parseInt(gstStudentLatest[phone].id)) {
                gstStudentLatest[phone] = inv;
            }
        } else {
            nonGstCount++;
            if (!nonGstStudentLatest[phone] || parseInt(inv.id) > parseInt(nonGstStudentLatest[phone].id)) {
                nonGstStudentLatest[phone] = inv;
            }
        }
    });

    let totBilled = 0;
    Object.values(studentLatest).forEach(inv => {
        totBilled += parseFloat(inv.originalTotalPayable || 0);
    });

    let gstBilled = 0;
    Object.values(gstStudentLatest).forEach(inv => {
        gstBilled += parseFloat(inv.originalTotalPayable || 0);
    });

    let nonGstBilled = 0;
    Object.values(nonGstStudentLatest).forEach(inv => {
        nonGstBilled += parseFloat(inv.originalTotalPayable || 0);
    });

    // 1. Total Amount Billed Card
    const amountEl = document.getElementById('total-invoice-amount');
    if (amountEl) {
        amountEl.innerText = '₹' + totBilled.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    const countSubEl = document.getElementById('total-invoice-count-sub');
    if (countSubEl) {
        countSubEl.innerText = totalCount + (totalCount === 1 ? ' Invoice' : ' Invoices');
    }

    // 2. GST Amount Billed Card
    const gstAmountEl = document.getElementById('gst-invoice-amount');
    if (gstAmountEl) {
        gstAmountEl.innerText = '₹' + gstBilled.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    const gstCountEl = document.getElementById('gst-invoice-count');
    if (gstCountEl) {
        gstCountEl.innerText = gstCount + (gstCount === 1 ? ' GST Invoice' : ' GST Invoices');
    }

    // 3. Non-GST Amount Billed Card
    const nonGstAmountEl = document.getElementById('nongst-invoice-amount');
    if (nonGstAmountEl) {
        nonGstAmountEl.innerText = '₹' + nonGstBilled.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    const nonGstCountEl = document.getElementById('nongst-invoice-count');
    if (nonGstCountEl) {
        nonGstCountEl.innerText = nonGstCount + (nonGstCount === 1 ? ' Non-GST Invoice' : ' Non-GST Invoices');
    }

    // Fallback updates for legacy IDs if present on page
    if (document.getElementById('total-invoice-count')) {
        document.getElementById('total-invoice-count').innerText = totalCount;
    }
}

// View/Generate Invoice or Bill by ID or No
function viewInvoiceById(id, invoiceNo, type) {
    let url = 'api/view_invoice.php?';
    if (id) {
        url += `id=${encodeURIComponent(id)}&`;
    } else if (invoiceNo) {
        url += `invoiceNo=${encodeURIComponent(invoiceNo)}&`;
    }
    if (type) url += `type=${encodeURIComponent(type)}&`;
    url += `t=${Date.now()}`;
    window.open(url, '_blank');
}

function viewInvoiceByNo(invoiceNo, type) {
    const list = (typeof generatedInvoices !== 'undefined' && Array.isArray(generatedInvoices)) ? generatedInvoices : (window.generatedInvoices || []);
    const inv = list.find(i => i.invoiceNo === invoiceNo || i.invoice_no === invoiceNo);
    if (inv) {
        viewInvoiceById(inv.id, invoiceNo, type);
    } else {
        viewInvoiceById(null, invoiceNo, type);
    }
}

function deleteInvoiceById(id, invoiceNo, type) {
    const label = invoiceNo ? `invoice ${invoiceNo}` : `bill #${id}`;
    if (!confirm(`Are you sure you want to delete ${label}? This will also delete all linked payment receipts and ledger transactions.`)) {
        return;
    }

    let url = 'api/delete_invoice.php?';
    if (id) {
        url += `id=${encodeURIComponent(id)}`;
    } else {
        url += `invoiceNo=${encodeURIComponent(invoiceNo)}`;
    }
    if (type) {
        url += `&type=${encodeURIComponent(type)}`;
    }

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully deleted.`);
                if (typeof loadInvoices === 'function') {
                    loadInvoices();
                }
                if (typeof loadReceipts === 'function') {
                    loadReceipts();
                }
            } else {
                alert('Error deleting: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Delete invoice error:', err);
            alert('Network error deleting invoice/bill.');
        });
}

function deleteInvoiceByNo(invoiceNo, type) {
    const list = (typeof generatedInvoices !== 'undefined' && Array.isArray(generatedInvoices)) ? generatedInvoices : (window.generatedInvoices || []);
    const inv = list.find(i => i.invoiceNo === invoiceNo || i.invoice_no === invoiceNo);
    if (inv) {
        deleteInvoiceById(inv.id, invoiceNo, type);
    } else {
        deleteInvoiceById(null, invoiceNo, type);
    }
}

function editInvoiceById(id) {
    const list = (typeof generatedInvoices !== 'undefined' && Array.isArray(generatedInvoices) && generatedInvoices.length > 0)
        ? generatedInvoices
        : (window.generatedInvoices || []);

    const invoice = list.find(inv => String(inv.id) === String(id));
    if (!invoice) {
        alert('Bill / invoice data not found for ID ' + id);
        return;
    }

    const currentNo = invoice.invoiceNo || invoice.invoice_no || '';
    if (document.getElementById('editInvId')) document.getElementById('editInvId').value = invoice.id;
    if (document.getElementById('editInvOriginalNo')) document.getElementById('editInvOriginalNo').value = currentNo;

    if (document.getElementById('editInvModalTitle')) {
        document.getElementById('editInvModalTitle').innerText = currentNo
            ? `Edit Invoice: ${currentNo}`
            : `Edit Unallocated Bill`;
    }
    if (document.getElementById('editInvBillToName')) document.getElementById('editInvBillToName').value = invoice.billToName || invoice.client_name || '';
    if (document.getElementById('editInvPhone')) document.getElementById('editInvPhone').value = invoice.phone || '';
    if (document.getElementById('editInvEmail')) document.getElementById('editInvEmail').value = invoice.email || '';
    if (document.getElementById('editInvGstNumber')) document.getElementById('editInvGstNumber').value = invoice.gstNumber || invoice.gst_number || '';
    
    let dVal = invoice.date || invoice.generatedAt;
    let formattedDate = '';
    if (dVal) {
        const cleanDate = dVal.toString().split(' ')[0];
        if (cleanDate.match(/^\d{4}-\d{2}-\d{2}$/)) {
            formattedDate = cleanDate;
        } else {
            try {
                formattedDate = new Date(dVal).toISOString().split('T')[0];
            } catch(e) {
                formattedDate = '';
            }
        }
    }
    if (document.getElementById('editInvDate')) {
        document.getElementById('editInvDate').value = formattedDate;
    }
    if (document.getElementById('editInvType')) document.getElementById('editInvType').value = (invoice.type || 'non-gst').toLowerCase();
    if (document.getElementById('editInvTotalPayable')) document.getElementById('editInvTotalPayable').value = parseFloat(invoice.originalTotalPayable || invoice.amount || 0).toFixed(2);

    const modal = document.getElementById('editInvoiceModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
        modal.classList.add('show');
        modal.classList.remove('hidden');
    }

    loadLinkedReceiptsForInvoice(currentNo, invoice.id);
}

function editInvoiceByNo(invoiceNo) {
    const list = (typeof generatedInvoices !== 'undefined' && Array.isArray(generatedInvoices)) ? generatedInvoices : (window.generatedInvoices || []);
    const inv = list.find(i => i.invoiceNo === invoiceNo || i.invoice_no === invoiceNo);
    if (inv) {
        editInvoiceById(inv.id);
    } else {
        alert('Invoice data not found');
    }
}

function closeEditInvoiceModal() {
    const modal = document.getElementById('editInvoiceModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.classList.remove('show');
        modal.classList.add('hidden');
    }
}

function loadLinkedReceiptsForInvoice(invoiceNo, invoiceId) {
    const container = document.getElementById('editInvReceiptsList');
    if (!container) return;

    container.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 20px;">Loading linked receipts...</p>';

    let url = `api/get_receipts.php?`;
    if (invoiceId) url += `invoiceId=${encodeURIComponent(invoiceId)}&`;
    if (invoiceNo) url += `invoiceNo=${encodeURIComponent(invoiceNo)}`;

    fetch(url)
        .then(res => res.json())
        .then(receipts => {
            if (!Array.isArray(receipts) || receipts.length === 0) {
                container.innerHTML = '<p style="text-align: center; color: #94a3b8; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 8px;">No payment receipts generated for this invoice yet.</p>';
                if (document.getElementById('editInvSummaryText')) {
                    document.getElementById('editInvSummaryText').innerText = 'Total Receipts: 0 | Total Paid: ₹0.00';
                }
                return;
            }

                        let totalPaid = 0;
            let html = `<table style="width: 100%; border-collapse: collapse; font-size: 13.5px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b; text-align: left; font-weight: 600;">
                        <th style="padding: 12px 14px;">Receipt No</th>
                        <th style="padding: 12px 14px;">Date</th>
                        <th style="padding: 12px 14px;">Payment Mode</th>
                        <th style="padding: 12px 14px;">Amount Paid</th>
                        <th style="padding: 12px 14px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>`;

            receipts.forEach(rec => {
                const items = typeof rec.items === 'string' ? JSON.parse(rec.items || '[]') : (rec.items || []);
                let paidAmt = 0;
                let modes = [];
                items.forEach(itm => {
                    paidAmt += parseFloat(itm.paidAmt ?? itm.amount ?? 0);
                    const modeVal = itm.mode || itm.paymentMode || 'Cash';
                    if (!modes.includes(modeVal)) modes.push(modeVal);
                });
                totalPaid += paidAmt;
                const displayMode = modes.length > 0 ? modes.join(', ') : 'Cash / Bank';
                const formattedDate = new Date(rec.date || rec.receipt_date).toLocaleDateString('en-GB');

                html += `<tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 12px 14px; font-weight: 600; color: #2563eb;">${rec.receiptNo}</td>
                    <td style="padding: 12px 14px; color: #334155;">${formattedDate}</td>
                    <td style="padding: 12px 14px;"><span style="background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">${displayMode}</span></td>
                    <td style="padding: 12px 14px; font-weight: 700; color: #059669;">₹${paidAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 })}</td>
                    <td style="padding: 12px 14px; text-align: right;">
                        <button type="button" onclick="openEditReceiptItemModal('${rec.receiptNo}', '${rec.id}', '${rec.invoiceNo}', '${rec.date || rec.receipt_date}', '${displayMode}', ${paidAmt})" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; margin-right: 6px; font-weight: 600;">
                            Edit Receipt
                        </button>
                        <button type="button" onclick="deleteLinkedReceiptInModal('${rec.receiptNo}', '${rec.invoiceNo}')" style="background: #ef4444; color: white; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600;">
                            Delete
                        </button>
                    </td>
                </tr>`;
            });

            html += `</tbody></table>`;
            container.innerHTML = html;

            if (document.getElementById('editInvSummaryText')) {
                document.getElementById('editInvSummaryText').innerText = `Total Receipts: ${receipts.length} | Total Paid: ₹${totalPaid.toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
            }
        })
        .catch(err => {
            console.error('Error fetching receipts:', err);
            container.innerHTML = '<p style="text-align: center; color: #ef4444; padding: 20px;">Failed to load linked receipts.</p>';
        });
}

function submitEditInvoiceHeader(event) {
    event.preventDefault();
    const id = document.getElementById('editInvId') ? document.getElementById('editInvId').value : '';
    const invoiceNo = document.getElementById('editInvOriginalNo').value;
    const billToName = document.getElementById('editInvBillToName').value;
    const phone = document.getElementById('editInvPhone').value;
    const email = document.getElementById('editInvEmail').value;
    const gstNumber = document.getElementById('editInvGstNumber').value;
    const date = document.getElementById('editInvDate').value;
    const type = document.getElementById('editInvType').value;
    const originalTotalPayable = parseFloat(document.getElementById('editInvTotalPayable').value);

    const payload = {
        id: id,
        invoiceNo: invoiceNo,
        billToName: billToName,
        phone: phone,
        email: email,
        gstNumber: gstNumber,
        date: date,
        type: type,
        originalTotalPayable: originalTotalPayable
    };

    fetch('api/save_invoice.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.invoiceNo) {
            if (data.justAllocated) {
                alert(`Bill total updated to ₹${originalTotalPayable.toLocaleString('en-IN', { minimumFractionDigits: 2 })}!\n\n🎉 Since the bill is now 100% paid, Official Tax Invoice #${data.invoiceNo} has been officially allocated!`);
            } else {
                alert('Bill / Invoice details updated successfully!');
            }
            closeEditInvoiceModal();
            loadInvoices();
        } else {
            alert('Failed to update invoice: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Error saving invoice:', err);
        alert('Error saving invoice details.');
    });
}


function openEditReceiptItemModal(receiptNo, id, invoiceNo, date, mode, amount) {
    document.getElementById('editRecId').value = id || '';
    document.getElementById('editRecNo').value = receiptNo;
    document.getElementById('editRecInvoiceNo').value = invoiceNo;
    
    if (date) {
        const cleanDate = date.toString().split(' ')[0];
        document.getElementById('editRecDate').value = cleanDate;
    }
    
    populatePaymentSelectWithOptions(document.getElementById('editRecMode'), mode || 'CASH');
    document.getElementById('editRecAmount').value = parseFloat(amount || 0).toFixed(2);
    
    if (document.getElementById('editRecModalTitle')) {
        document.getElementById('editRecModalTitle').innerText = `Edit Receipt: ${receiptNo}`;
    }
    
    const modal = document.getElementById('editReceiptItemModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
        modal.classList.add('show');
        modal.classList.remove('hidden');
    }
}

function closeEditReceiptItemModal() {
    const modal = document.getElementById('editReceiptItemModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.classList.remove('show');
        modal.classList.add('hidden');
    }
}

function submitEditReceiptItem(event) {
    event.preventDefault();
    const id = document.getElementById('editRecId').value;
    const receiptNo = document.getElementById('editRecNo').value;
    const invoiceNo = document.getElementById('editRecInvoiceNo').value;
    const date = document.getElementById('editRecDate').value;
    const mode = document.getElementById('editRecMode').value;
    const amount = parseFloat(document.getElementById('editRecAmount').value);

    const items = [
        {
            desc: `Payment for Invoice ${invoiceNo}`,
            mode: mode,
            paymentMode: mode,
            date: date,
            paidAmt: amount,
            amount: amount
        }
    ];

    const payload = {
        id: id,
        receiptNo: receiptNo,
        invoiceNo: invoiceNo,
        date: date,
        receiptDate: date,
        items: items
    };

    fetch('api/save_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success || data.receiptNo) {
            alert(`Receipt ${receiptNo} updated successfully!`);
            closeEditReceiptItemModal();
            loadLinkedReceiptsForInvoice(invoiceNo);
            loadInvoices();
        } else {
            alert('Failed to update receipt: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Error saving receipt:', err);
        alert('Error saving receipt details.');
    });
}

function deleteLinkedReceiptInModal(receiptNo, invoiceNo) {
    if (!confirm(`Are you sure you want to delete receipt ${receiptNo}?`)) return;

    fetch('api/delete_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiptNo: receiptNo })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`Receipt ${receiptNo} deleted.`);
            loadLinkedReceiptsForInvoice(invoiceNo);
            loadInvoices();
        } else {
            alert('Failed to delete receipt: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Error deleting receipt:', err);
        alert('Error deleting receipt.');
    });
}



function closePayInvoiceModal() {
    const modal = document.getElementById('payInvoiceModal');
    if (modal) {
        modal.style.display = 'none';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        modal.classList.remove('show');
        modal.classList.add('hidden');
    }
}


function payInvoiceById(id) {
    const list = (typeof generatedInvoices !== 'undefined' && Array.isArray(generatedInvoices) && generatedInvoices.length > 0)
        ? generatedInvoices
        : (window.generatedInvoices || []);

    const inv = list.find(i => String(i.id) === String(id));

    if (!inv) {
        fetch('api/get_invoices.php')
            .then(res => res.json())
            .then(invoices => {
                if (Array.isArray(invoices)) {
                    window.generatedInvoices = invoices;
                    const found = invoices.find(i => String(i.id) === String(id));
                    if (found) {
                        openQuickPayModal(found);
                    } else {
                        alert('Bill details not found for ID ' + id);
                    }
                }
            })
            .catch(err => console.error('Error fetching invoice for payment:', err));
        return;
    }

    openQuickPayModal(inv);
}

function payInvoiceByNo(invoiceNo) {
    const list = (typeof generatedInvoices !== 'undefined' && Array.isArray(generatedInvoices) && generatedInvoices.length > 0)
        ? generatedInvoices
        : (window.generatedInvoices || []);

    const inv = list.find(i => (i.invoiceNo === invoiceNo || i.invoice_no === invoiceNo));

    if (inv) {
        payInvoiceById(inv.id);
    } else {
        fetch('api/get_invoices.php')
            .then(res => res.json())
            .then(invoices => {
                if (Array.isArray(invoices)) {
                    window.generatedInvoices = invoices;
                    const found = invoices.find(i => (i.invoiceNo === invoiceNo || i.invoice_no === invoiceNo));
                    if (found) {
                        openQuickPayModal(found);
                    } else {
                        alert('Invoice details not found for invoice ' + invoiceNo);
                    }
                }
            })
            .catch(err => console.error('Error fetching invoice for payment:', err));
    }
}


function openQuickPayModal(inv) {
    window.currentQuickPayInvoice = inv;
    const payModal = document.getElementById('payInvoiceModal');
    const receiptForm = document.getElementById('receiptInvoiceNo');

    const origTotal = parseFloat(inv.originalTotalPayable || inv.amount || 0);
    const cumPaid = parseFloat(inv.cumulativeTotalPaid || inv.paidAmount || 0);
    const pendingAmt = Math.max(0, origTotal - cumPaid);

    if (payModal) {
        if (document.getElementById('payInvId')) document.getElementById('payInvId').value = inv.id;
        document.getElementById('payInvNo').value = inv.invoiceNo || inv.invoice_no || '';
        document.getElementById('payInvBillToName').value = inv.billToName || inv.client_name || '';
        document.getElementById('payInvPhone').value = inv.phone || '';
        document.getElementById('payInvEmail').value = inv.email || '';
        document.getElementById('payInvGstNumber').value = inv.gstNumber || inv.gst_number || '';
        document.getElementById('payInvType').value = (inv.type || 'non-gst').toLowerCase();
        document.getElementById('payInvOriginalTotal').value = origTotal;
        document.getElementById('payInvCumulativePaid').value = cumPaid;

        if (document.getElementById('payModalInvoiceNo')) {
            document.getElementById('payModalInvoiceNo').innerText = inv.invoiceNo
                ? `Invoice #${inv.invoiceNo}`
                : `Unallocated (Pending 100% Pay)`;
        }
        if (document.getElementById('payModalClientName')) {
            document.getElementById('payModalClientName').innerText = inv.billToName || inv.client_name || '-';
        }
        if (document.getElementById('payModalTotalAmount')) {
            document.getElementById('payModalTotalAmount').innerText = '₹' + origTotal.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        }
        if (document.getElementById('payModalPendingAmount')) {
            document.getElementById('payModalPendingAmount').innerText = '₹' + pendingAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 });
        }

        if (document.getElementById('payDate')) {
            document.getElementById('payDate').value = new Date().toISOString().split('T')[0];
        }
        if (document.getElementById('payAmount')) {
            const amtInput = document.getElementById('payAmount');
            amtInput.value = pendingAmt.toFixed(2);
            amtInput.max = pendingAmt.toFixed(2);
            amtInput.oninput = function() {
                const val = parseFloat(this.value || 0);
                if (val > (pendingAmt + 0.01)) {
                    this.style.borderColor = '#ef4444';
                    this.style.color = '#ef4444';
                } else {
                    this.style.borderColor = '#cbd5e1';
                    this.style.color = '#0284c7';
                }
            };
        }
        if (document.getElementById('payMode')) {
            document.getElementById('payMode').value = 'UPI';
        }

        
        payModal.style.display = 'flex';
        payModal.style.opacity = '1';
        payModal.style.pointerEvents = 'auto';
        payModal.classList.add('show');
        payModal.classList.remove('hidden');

    } else if (receiptForm) {
        if (typeof openCreateReceiptModal === 'function') {
            openCreateReceiptModal();
        } else {
            const listCont = document.getElementById('receipt-list-container');
            const formCont = document.getElementById('receipt-form-container');
            if (listCont) listCont.classList.add('hidden');
            if (formCont) formCont.classList.remove('hidden');
        }

        document.getElementById('receiptInvoiceNo').value = inv.invoiceNo || inv.invoice_no || '';
        if (document.getElementById('receiptBillToName')) document.getElementById('receiptBillToName').value = inv.billToName || inv.client_name || '';
        if (document.getElementById('receiptPhone')) document.getElementById('receiptPhone').value = inv.phone || '';
        if (document.getElementById('receiptEmail')) document.getElementById('receiptEmail').value = inv.email || '';
        if (document.getElementById('receiptGstNumber')) document.getElementById('receiptGstNumber').value = inv.gstNumber || inv.gst_number || '';
        if (document.getElementById('receiptType')) document.getElementById('receiptType').value = (inv.type || 'non-gst').toLowerCase();
        if (document.getElementById('receiptOriginalTotal')) document.getElementById('receiptOriginalTotal').value = origTotal.toFixed(2);
        if (document.getElementById('receiptCumulativePaid')) document.getElementById('receiptCumulativePaid').value = cumPaid.toFixed(2);
        if (document.getElementById('receiptDate')) document.getElementById('receiptDate').value = new Date().toISOString().split('T')[0];

        const tbody = document.getElementById('receiptItemsTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td><input type="text" class="form-input rec-item-desc" value="${(function() {
        try {
            const invItems = typeof inv.items === 'string' ? JSON.parse(inv.items || '[]') : (inv.items || []);
            if (Array.isArray(invItems) && invItems.length > 0) {
                const descs = invItems.map(i => i.description).filter(Boolean);
                if (descs.length > 0) return descs.join(', ');
            }
        } catch(e) {}
        return 'Payment for Invoice ' + (inv.invoiceNo || inv.invoice_no);
    })()}" required></td>
                    <td>
                        <select class="form-input rec-item-mode"></select>
                    </td>
                    <td><input type="date" class="form-input rec-item-date" value="${new Date().toISOString().split('T')[0]}" required></td>
                    <td><input type="number" class="form-input rec-item-amount" value="${pendingAmt.toFixed(2)}" step="0.01" min="0" oninput="calculateReceiptTotals()" required></td>
                    <td><button type="button" class="btn-action" style="background:#ef4444; color:white; border:none; padding: 6px 12px; border-radius: 4px;" onclick="removeReceiptItemRow(this)">−</button></td>
                </tr>
            `;
            const modeSel = tbody.querySelector('.rec-item-mode');
            if (modeSel && typeof populatePaymentSelectWithOptions === 'function') {
                populatePaymentSelectWithOptions(modeSel, 'CASH');
            }
        }

        if (typeof calculateReceiptTotals === 'function') {
            calculateReceiptTotals();
        }
    } else {
        window.location.href = `receipts.php?pay_invoice=${encodeURIComponent(inv.invoiceNo || inv.invoice_no)}`;
    }
}

function submitQuickPayment(e) {
    e.preventDefault();

    const invoiceId = document.getElementById('payInvId') ? document.getElementById('payInvId').value : (window.currentQuickPayInvoice ? window.currentQuickPayInvoice.id : '');
    const invoiceNo = document.getElementById('payInvNo').value.trim();
    const billToName = document.getElementById('payInvBillToName').value.trim();
    const phone = document.getElementById('payInvPhone').value.trim();
    const email = document.getElementById('payInvEmail').value.trim();
    const gstNumber = document.getElementById('payInvGstNumber').value.trim();
    const type = document.getElementById('payInvType').value;
    const originalTotalPayable = parseFloat(document.getElementById('payInvOriginalTotal').value || 0);
    const cumulativeTotalPaid = parseFloat(document.getElementById('payInvCumulativePaid').value || 0);

    const payMode = document.getElementById('payMode').value;
    const payDate = document.getElementById('payDate').value;
    const payAmount = parseFloat(document.getElementById('payAmount').value || 0);

    const pendingAmt = Math.max(0, originalTotalPayable - cumulativeTotalPaid);
    if (payAmount <= 0) {
        alert('Payment amount must be greater than zero.');
        return;
    }
    if (payAmount > (pendingAmt + 0.01)) {
        alert(`Payment amount (₹${payAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 })}) cannot be more than the remaining pending balance of ₹${pendingAmt.toLocaleString('en-IN', { minimumFractionDigits: 2 })}.`);
        document.getElementById('payAmount').value = pendingAmt.toFixed(2);
        return;
    }

    const items = [
        {
            description: (function() {
                try {
                    if (window.currentQuickPayInvoice) {
                        const invItems = typeof window.currentQuickPayInvoice.items === 'string' ? JSON.parse(window.currentQuickPayInvoice.items || '[]') : (window.currentQuickPayInvoice.items || []);
                        if (Array.isArray(invItems) && invItems.length > 0) {
                            const descs = invItems.map(i => i.description).filter(Boolean);
                            if (descs.length > 0) return descs.join(', ');
                        }
                    }
                } catch(e) {}
                return 'Payment for ' + (invoiceNo ? ('Invoice ' + invoiceNo) : 'Unallocated Invoice');
            })(),
            paymentMode: payMode,
            date: payDate,
            amount: payAmount,
            paidAmt: payAmount
        }
    ];

    const payload = {
        editId: '',
        type: type,
        billToName: billToName,
        phone: phone,
        email: email,
        gstNumber: gstNumber,
        receiptDate: payDate,
        invoice_id: invoiceId,
        invoice_no: invoiceNo,
        receiptNo: '',
        items: items,
        originalTotalPayable: originalTotalPayable,
        cumulativeTotalPaid: cumulativeTotalPaid + payAmount
    };

    fetch('api/save_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closePayInvoiceModal();
            if (data.allocatedInvoiceNo) {
                alert(`Payment of ₹${payAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 })} recorded successfully!\nReceipt No: ${data.receiptNo}\n\n🎉 100% Bill Settled! Official Tax Invoice #${data.allocatedInvoiceNo} has been allocated!`);
            } else {
                alert(`Payment of ₹${payAmount.toLocaleString('en-IN', { minimumFractionDigits: 2 })} recorded successfully!\nReceipt No: ${data.receiptNo}`);
            }
            if (typeof loadInvoices === 'function') {
                loadInvoices();
            }
            if (typeof loadReceipts === 'function') {
                loadReceipts();
            }
            if (data.receiptNo) {
                if (data.token) {
                    window.open(`api/view_receipt.php?token=${data.token}`, '_blank');
                } else {
                    window.open(`api/view_receipt.php?receiptNo=${encodeURIComponent(data.receiptNo)}`, '_blank');
                }
            }
            if (data.allocatedInvoiceNo) {
                window.open(`api/view_invoice.php?invoiceNo=${encodeURIComponent(data.allocatedInvoiceNo)}`, '_blank');
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to save payment receipt.'));
        }
    })
    .catch(err => {
        console.error('Save quick payment error:', err);
        alert('Network error saving payment receipt.');
    });
}



var appPaymentModes = window.appPaymentModes || [];

function fetchAppPaymentModes() {
    if (appPaymentModes.length > 0) {
        return Promise.resolve(appPaymentModes);
    }
    return fetch('api/get_payment_modes.php')
        .then(res => res.json())
        .then(modes => {
            if (Array.isArray(modes) && modes.length > 0) {
                appPaymentModes = modes;
                populatePaymentModeSelects();
            }
            return appPaymentModes;
        })
        .catch(err => {
            console.error('Error fetching payment modes from settings:', err);
            return appPaymentModes;
        });
}

function populatePaymentSelectWithOptions(selectEl, selectedValue, allowCustomFallback = false) {
    if (!selectEl) return;
    let valToMatch = (selectedValue || '').trim();

    if (!Array.isArray(appPaymentModes) || appPaymentModes.length === 0) {
        fetchAppPaymentModes().then(() => populatePaymentSelectWithOptions(selectEl, selectedValue, allowCustomFallback));
        return;
    }

    // Ignore legacy hardcoded values if they are not defined in Settings
    const legacyValues = ['upi', 'cash', 'bank transfer', 'card', 'credit card', 'debit card', 'cheque'];
    if (valToMatch && legacyValues.includes(valToMatch.toLowerCase())) {
        const existsInSettings = appPaymentModes.some(m => (m.mode_name || m).trim().toLowerCase() === valToMatch.toLowerCase());
        if (!existsInSettings) {
            valToMatch = '';
        }
    }

    let foundMatch = false;
    let html = '';

    appPaymentModes.forEach((m, idx) => {
        const mName = (m.mode_name || m).trim();
        let isSel = false;
        if (valToMatch) {
            if (valToMatch.toLowerCase() === mName.toLowerCase()) {
                isSel = true;
                foundMatch = true;
            }
        } else if (idx === 0) {
            isSel = true;
            foundMatch = true;
        }
        html += `<option value="${mName}" ${isSel ? 'selected' : ''}>${mName}</option>`;
    });

    if (valToMatch && !foundMatch && allowCustomFallback) {
        html += `<option value="${valToMatch}" selected>${valToMatch}</option>`;
    }

    selectEl.innerHTML = html;
}

function populatePaymentModeSelects() {
    const payMode = document.getElementById('payMode');
    if (payMode) populatePaymentSelectWithOptions(payMode, payMode.value);
    const editRecMode = document.getElementById('editRecMode');
    if (editRecMode) populatePaymentSelectWithOptions(editRecMode, editRecMode.value);
    const nonGstMode = document.getElementById('nonGstPaymentMode');
    if (nonGstMode) populatePaymentSelectWithOptions(nonGstMode, nonGstMode.value);
    const gstMode = document.getElementById('gstPaymentMode');
    if (gstMode) populatePaymentSelectWithOptions(gstMode, gstMode.value);
    const voucherMode = document.getElementById('voucherMode');
    if (voucherMode) populatePaymentSelectWithOptions(voucherMode, voucherMode.value);
}

document.addEventListener('DOMContentLoaded', () => {
    fetchAppPaymentModes();
});

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const params = new URLSearchParams(window.location.search);
        const clientName = params.get('client_name');
        const phone = params.get('phone');
        const email = params.get('email');
        
        if (clientName) {
            if (document.getElementById('invoice-form-container')) {
                document.getElementById('invoice-form-container').classList.remove('hidden');
            }
            if (document.getElementById('invoiceBillToName')) document.getElementById('invoiceBillToName').value = clientName;
            if (document.getElementById('invoicePhone')) document.getElementById('invoicePhone').value = phone || '';
            if (document.getElementById('invoiceEmail')) document.getElementById('invoiceEmail').value = email || '';
            if (document.getElementById('nonGstBillToName')) document.getElementById('nonGstBillToName').value = clientName;
            if (document.getElementById('nonGstPhone')) document.getElementById('nonGstPhone').value = phone || '';
            if (document.getElementById('nonGstEmail')) document.getElementById('nonGstEmail').value = email || '';
        }
    }, 200);
});


// ==========================================
// INVOICE POPUP MODAL FUNCTIONS (GST / NON-GST)
// ==========================================

function setInvoiceClientFieldsReadOnly(isReadOnly) {
    const fields = [
        'nonGstBillToName', 'nonGstPhone', 'nonGstEmail',
        'gstBillToName', 'gstPhone', 'gstEmail', 'gstModalNumber'
    ];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.readOnly = isReadOnly;
            if (isReadOnly) {
                el.style.backgroundColor = '#f8fafc';
                el.style.cursor = 'not-allowed';
                el.style.color = '#475569';
                el.removeAttribute('pattern');
                el.removeAttribute('maxlength');
                el.removeAttribute('minlength');
                el.setCustomValidity('');
            } else {
                el.style.backgroundColor = '';
                el.style.cursor = '';
                el.style.color = '';
                if (id === 'nonGstPhone' || id === 'gstPhone') {
                    el.setAttribute('pattern', '[0-9]{10}');
                    el.setAttribute('maxlength', '10');
                    el.setAttribute('minlength', '10');
                }
            }
        }
    });
}
window.setInvoiceClientFieldsReadOnly = setInvoiceClientFieldsReadOnly;

function switchToGstModal() {
    const isLocked = document.getElementById('nonGstBillToName') && document.getElementById('nonGstBillToName').readOnly;
    const name = document.getElementById('nonGstBillToName') ? document.getElementById('nonGstBillToName').value : '';
    const phone = document.getElementById('nonGstPhone') ? document.getElementById('nonGstPhone').value : '';
    const email = document.getElementById('nonGstEmail') ? document.getElementById('nonGstEmail').value : '';

    if (name && document.getElementById('gstBillToName')) document.getElementById('gstBillToName').value = name;
    if (phone && document.getElementById('gstPhone')) document.getElementById('gstPhone').value = phone;
    if (email && document.getElementById('gstEmail')) document.getElementById('gstEmail').value = email;

    document.getElementById('nonGstInvoiceModal').classList.remove('show');
    document.getElementById('gstInvoiceModal').classList.add('show');
    if (typeof populatePaymentModeSelects === 'function') populatePaymentModeSelects();

    if (isLocked) {
        setInvoiceClientFieldsReadOnly(true);
    }
}

function switchToNonGstModal() {
    const isLocked = document.getElementById('gstBillToName') && document.getElementById('gstBillToName').readOnly;
    const name = document.getElementById('gstBillToName') ? document.getElementById('gstBillToName').value : '';
    const phone = document.getElementById('gstPhone') ? document.getElementById('gstPhone').value : '';
    const email = document.getElementById('gstEmail') ? document.getElementById('gstEmail').value : '';

    if (name && document.getElementById('nonGstBillToName')) document.getElementById('nonGstBillToName').value = name;
    if (phone && document.getElementById('nonGstPhone')) document.getElementById('nonGstPhone').value = phone;
    if (email && document.getElementById('nonGstEmail')) document.getElementById('nonGstEmail').value = email;

    document.getElementById('gstInvoiceModal').classList.remove('show');
    document.getElementById('nonGstInvoiceModal').classList.add('show');
    if (typeof populatePaymentModeSelects === 'function') populatePaymentModeSelects();

    if (isLocked) {
        setInvoiceClientFieldsReadOnly(true);
    }
}

function closeGstModal() {
    document.getElementById('gstInvoiceModal').classList.remove('show');
    document.getElementById('gstInvoiceForm').reset();
    setInvoiceClientFieldsReadOnly(false);
    
    const form = document.getElementById('gstInvoiceForm');
    delete form.dataset.editInvoiceNo;
    delete form.dataset.continueFrom;
    delete form.dataset.originalTotalPayable;
    delete form.dataset.cumulativeTotalPaid;
    
    document.getElementById('gstPaymentSummarySection').style.display = 'none';
    document.getElementById('btnGstGenerate').style.display = 'none';
    
    const container = document.getElementById('gstItemsContainer');
    container.innerHTML = `
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
    `;
    
    const lookupContainer = document.getElementById('existingUserGst');
    if (lookupContainer) lookupContainer.innerHTML = '';
}

function closeNonGstModal() {
    document.getElementById('nonGstInvoiceModal').classList.remove('show');
    document.getElementById('nonGstInvoiceForm').reset();
    setInvoiceClientFieldsReadOnly(false);
    
    const form = document.getElementById('nonGstInvoiceForm');
    delete form.dataset.editInvoiceNo;
    delete form.dataset.continueFrom;
    delete form.dataset.originalTotalPayable;
    delete form.dataset.cumulativeTotalPaid;
    
    document.getElementById('nonGstPaymentSummarySection').style.display = 'none';
    document.getElementById('btnNonGstGenerate').style.display = 'none';
    
    const container = document.getElementById('nonGstItemsContainer');
    container.innerHTML = `
        <div class="invoice-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
            <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
            <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
            <button type="button" class="btn-add-item" onclick="addNonGstItem()">+</button>
        </div>
    `;
    
    const lookupContainer = document.getElementById('existingUserNonGst');
    if (lookupContainer) lookupContainer.innerHTML = '';
}

function addGstItem() {
    const rows = document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst');
    let allValid = true;
    rows.forEach(row => {
        const descInput = row.querySelector('.gst-desc');
        const amtInput = row.querySelector('.gst-total-incl');
        if (descInput && !descInput.value.trim()) {
            descInput.reportValidity();
            allValid = false;
        } else if (amtInput && (!amtInput.value.trim() || parseFloat(amtInput.value) < 0)) {
            amtInput.reportValidity();
            allValid = false;
        }
    });
    
    if (!allValid) return;

    const container = document.getElementById('gstItemsContainer');
    const row = document.createElement('div');
    row.className = 'invoice-item-row-gst';
    row.style = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
    row.innerHTML = `
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
        <button type="button" class="btn-remove-item" onclick="removeItem(this)">−</button>
    `;
    container.appendChild(row);
}

function addNonGstItem() {
    const rows = document.querySelectorAll('#nonGstItemsContainer .invoice-item-row');
    let allValid = true;
    rows.forEach(row => {
        const descInput = row.querySelector('.nongst-item-desc');
        const amtInput = row.querySelector('.nongst-item-amount');
        if (descInput && !descInput.value.trim()) {
            descInput.reportValidity();
            allValid = false;
        } else if (amtInput && (!amtInput.value.trim() || parseFloat(amtInput.value) < 0)) {
            amtInput.reportValidity();
            allValid = false;
        }
    });
    
    if (!allValid) return;

    const container = document.getElementById('nonGstItemsContainer');
    const row = document.createElement('div');
    row.className = 'invoice-item-row';
    row.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
    row.innerHTML = `
        <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
        <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
        <button type="button" class="btn-remove-item" onclick="removeNonGstItem(this)">−</button>
    `;
    container.appendChild(row);
    onNonGstItemAmountChange();
}

function removeNonGstItem(button) {
    button.parentElement.remove();
    onNonGstItemAmountChange();
}

function removeItem(button) {
    button.parentElement.remove();
    onGstItemAmountChange();
}

function onNonGstItemAmountChange() {
    const section = document.getElementById('nonGstPaymentSummarySection');
    if (section && section.style.display !== 'none') {
        updateNonGstSummary();
    }
}

function onGstItemAmountChange() {
    const section = document.getElementById('gstPaymentSummarySection');
    if (section && section.style.display !== 'none') {
        updateGstSummary();
    }
}

function clickGstDone() {
    const billToName = document.getElementById('gstBillToName');
    const phone = document.getElementById('gstPhone');
    const gstNumber = document.getElementById('gstModalNumber');
    
    if (!billToName.value.trim()) {
        billToName.reportValidity();
        return;
    }
    if (!phone.readOnly) {
        const phoneClean = (phone.value || '').trim().replace(/[^0-9]/g, '');
        if (phoneClean.length !== 10) {
            phone.setCustomValidity('Please enter a valid 10-digit phone number.');
            phone.reportValidity();
            return;
        } else {
            phone.setCustomValidity('');
        }
    } else {
        phone.setCustomValidity('');
    }
    if (!gstNumber.value.trim()) {
        gstNumber.reportValidity();
        return;
    }
    
    const rows = document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst');
    if (rows.length === 0) {
        alert('Please add at least one invoice item.');
        return;
    }
    
    let allValid = true;
    rows.forEach(row => {
        const descInput = row.querySelector('.gst-desc');
        const amtInput = row.querySelector('.gst-total-incl');
        if (descInput && !descInput.value.trim()) {
            descInput.reportValidity();
            allValid = false;
        } else if (amtInput && (!amtInput.value.trim() || parseFloat(amtInput.value) < 0)) {
            amtInput.reportValidity();
            allValid = false;
        }
    });
    
    if (!allValid) return;
    
    const dateInput = document.getElementById('gstPaymentDate');
    if (!dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    let totalChargesExcl = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.gst-total-incl');
        totalChargesExcl += parseFloat(amtInput.value) || 0;
    });
    const totalPayableWithGst = totalChargesExcl * 1.18;
    
    const amountPaidInput = document.getElementById('gstAmountPaid');
    if (!amountPaidInput.value) {
        amountPaidInput.value = totalPayableWithGst.toFixed(2);
    }
    
    document.getElementById('gstPaymentSummarySection').style.display = 'block';
    document.getElementById('btnGstGenerate').style.display = 'inline-block';
    
    updateGstSummary();
    
    document.getElementById('gstPaymentSummarySection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clickNonGstDone() {
    const billToName = document.getElementById('nonGstBillToName');
    const phone = document.getElementById('nonGstPhone');
    
    if (!billToName.value.trim()) {
        billToName.reportValidity();
        return;
    }
    if (!phone.readOnly) {
        const phoneClean = (phone.value || '').trim().replace(/[^0-9]/g, '');
        if (phoneClean.length !== 10) {
            phone.setCustomValidity('Please enter a valid 10-digit phone number.');
            phone.reportValidity();
            return;
        } else {
            phone.setCustomValidity('');
        }
    } else {
        phone.setCustomValidity('');
    }
    
    const rows = document.querySelectorAll('#nonGstItemsContainer .invoice-item-row');
    if (rows.length === 0) {
        alert('Please add at least one invoice item.');
        return;
    }
    
    let allValid = true;
    rows.forEach(row => {
        const descInput = row.querySelector('.nongst-item-desc');
        const amtInput = row.querySelector('.nongst-item-amount');
        if (!descInput.value.trim()) {
            descInput.reportValidity();
            allValid = false;
        } else if (!amtInput.value.trim() || parseFloat(amtInput.value) < 0) {
            amtInput.reportValidity();
            allValid = false;
        }
    });
    
    if (!allValid) return;
    
    const dateInput = document.getElementById('nonGstPaymentDate');
    if (!dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.nongst-item-amount');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const amountPaidInput = document.getElementById('nonGstAmountPaid');
    if (!amountPaidInput.value) {
        amountPaidInput.value = totalItemsAmt.toFixed(2);
    }
    
    document.getElementById('nonGstPaymentSummarySection').style.display = 'block';
    document.getElementById('btnNonGstGenerate').style.display = 'inline-block';
    
    updateNonGstSummary();
    
    document.getElementById('nonGstPaymentSummarySection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function updateNonGstSummary() {
    const rows = document.querySelectorAll('#nonGstItemsContainer .invoice-item-row');
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.nongst-item-amount');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const form = document.getElementById('nonGstInvoiceForm');
    
    let totalPayable = totalItemsAmt;
    let prevPaid = 0;
    
    if (form.dataset.continueFrom) {
        totalPayable = parseFloat(form.dataset.originalTotalPayable) || totalItemsAmt;
        prevPaid = parseFloat(form.dataset.cumulativeTotalPaid) || 0;
    }
    
    const amountPaid = parseFloat(document.getElementById('nonGstAmountPaid').value) || 0;
    const totalPaidSoFar = prevPaid + amountPaid;
    const balanceDue = Math.max(0, totalPayable - totalPaidSoFar);
    
    document.getElementById('summaryNonGstTotal').innerText = '₹' + totalPayable.toFixed(2);
    document.getElementById('summaryNonGstPaid').innerText = '₹' + amountPaid.toFixed(2);
    
    const dueElement = document.getElementById('summaryNonGstDue');
    dueElement.innerText = '₹' + balanceDue.toFixed(2);
    if (balanceDue > 0.01) {
        dueElement.style.color = '#dc2626';
    } else {
        dueElement.style.color = '#10b981';
    }
}

function updateGstSummary() {
    const rows = document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst');
    let totalChargesExcl = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.gst-total-incl');
        totalChargesExcl += parseFloat(amtInput.value) || 0;
    });
    
    const form = document.getElementById('gstInvoiceForm');
    
    let totalBaseCharges = totalChargesExcl;
    let totalPayable = totalChargesExcl * 1.18;
    let prevPaid = 0;
    
    if (form.dataset.continueFrom) {
        totalPayable = parseFloat(form.dataset.originalTotalPayable) || (totalChargesExcl * 1.18);
        totalBaseCharges = totalPayable / 1.18;
        prevPaid = parseFloat(form.dataset.cumulativeTotalPaid) || 0;
    }
    
    const gstAmt = totalPayable - totalBaseCharges;
    
    const amountPaid = parseFloat(document.getElementById('gstAmountPaid').value) || 0;
    const totalPaidSoFar = prevPaid + amountPaid;
    const balanceDue = Math.max(0, totalPayable - totalPaidSoFar);
    
    document.getElementById('summaryGstCharges').innerText = '₹' + totalBaseCharges.toFixed(2);
    document.getElementById('summaryGstTax').innerText = '₹' + gstAmt.toFixed(2);
    document.getElementById('summaryGstTotal').innerText = '₹' + totalPayable.toFixed(2);
    document.getElementById('summaryGstPaid').innerText = '₹' + amountPaid.toFixed(2);
    
    const dueElement = document.getElementById('summaryGstDue');
    dueElement.innerText = '₹' + balanceDue.toFixed(2);
    if (balanceDue > 0.01) {
        dueElement.style.color = '#dc2626';
    } else {
        dueElement.style.color = '#10b981';
    }
}

async function generateGstInvoice(event) {
    if (event) event.preventDefault();
    const invoiceNo = document.getElementById('gstInvoiceForm').dataset.editInvoiceNo;
    
    const phoneInput = document.getElementById('gstPhone');
    const phoneClean = (phoneInput ? phoneInput.value : '').trim().replace(/[^0-9]/g, '');
    if (!phoneInput.readOnly && phoneClean.length !== 10) {
        alert('Please enter a valid 10-digit phone number.');
        return;
    }
    
    const paymentMode = document.getElementById('gstPaymentMode').value;
    const paymentDate = document.getElementById('gstPaymentDate').value;
    const amountPaid = parseFloat(document.getElementById('gstAmountPaid').value) || 0;
    
    if (!paymentMode) {
        alert('Please select a payment mode.');
        return;
    }
    
    let totalChargesExcl = 0;
    document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst').forEach(row => {
        const amtVal = parseFloat(row.querySelector('.gst-total-incl').value) || 0;
        totalChargesExcl += amtVal;
    });
    const totalPayableWithGst = totalChargesExcl * 1.18;
    
    const ratio = totalPayableWithGst > 0 ? (amountPaid / totalPayableWithGst) : 0;
    
    const items = [];
    const rows = document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst');
    rows.forEach((row, index) => {
        const desc = row.querySelector('.gst-desc').value;
        const sacCode = row.querySelector('.gst-sac') ? row.querySelector('.gst-sac').value : '';
        const charges = parseFloat(row.querySelector('.gst-total-incl').value) || 0;
        const hasDesc = row.querySelector('.gst-desc-check').checked;
        
        const gst = charges * 0.18;
        const totalIncl = charges + gst;
        
        let itemPaid = totalIncl * ratio;
        if (index === rows.length - 1) {
            let sumPaidPrev = 0;
            items.forEach(itm => sumPaidPrev += parseFloat(itm.paidAmt));
            itemPaid = amountPaid - sumPaidPrev;
        }
        
        items.push({
            description: desc,
            sacCode: sacCode,
            paymentMode: paymentMode,
            date: paymentDate,
            totalInclTax: totalIncl.toFixed(2),
            paidAmt: itemPaid.toFixed(2),
            gst: gst.toFixed(2),
            charges: charges.toFixed(2),
            hasDesc: hasDesc
        });
    });

    const form = document.getElementById('gstInvoiceForm');
    const formData = {
        type: 'gst',
        billToName: document.getElementById('gstBillToName').value,
        phone: phoneClean,
        gstNumber: document.getElementById('gstModalNumber').value,
        email: document.getElementById('gstEmail').value,
        address: (document.getElementById('gstAddress') ? document.getElementById('gstAddress').value : ''),
        items: JSON.stringify(items),
        invoiceNo: invoiceNo || null
    };

    const firstItemDate = items.length > 0 ? items[0].date : null;
    if (firstItemDate) formData.date = firstItemDate;

    if (form.dataset.continueFrom) formData.continueFrom = form.dataset.continueFrom;
    if (form.dataset.originalTotalPayable) formData.originalTotalPayable = form.dataset.originalTotalPayable;
    if (form.dataset.cumulativeTotalPaid) formData.cumulativeTotalPaid = form.dataset.cumulativeTotalPaid;

    saveInvoice(formData);
    closeGstModal();
}

async function generateNonGstInvoice(event) {
    if (event) event.preventDefault();
    const form = document.getElementById('nonGstInvoiceForm');
    const invoiceNo = form.dataset.editInvoiceNo;
    
    const phoneInput = document.getElementById('nonGstPhone');
    const phoneClean = (phoneInput ? phoneInput.value : '').trim().replace(/[^0-9]/g, '');
    if (!phoneInput.readOnly && phoneClean.length !== 10) {
        alert('Please enter a valid 10-digit phone number.');
        return;
    }
    
    const paymentMode = document.getElementById('nonGstPaymentMode').value;
    const paymentDate = document.getElementById('nonGstPaymentDate').value;
    const totalPaid = parseFloat(document.getElementById('nonGstAmountPaid').value) || 0;
    
    if (!paymentMode) {
        alert('Please select a Payment Mode');
        return;
    }
    if (!paymentDate) {
        alert('Please select a Payment Date');
        return;
    }
    
    const items = [];
    const rows = document.querySelectorAll('#nonGstItemsContainer .invoice-item-row');
    
    rows.forEach((row, idx) => {
        const desc = row.querySelector('.nongst-item-desc').value;
        const amt = parseFloat(row.querySelector('.nongst-item-amount').value) || 0;
        const paidAmt = (idx === 0) ? totalPaid : 0;
        
        items.push({
            description: desc,
            paymentMode: paymentMode,
            date: paymentDate,
            amount: amt.toFixed(2),
            paidAmt: paidAmt.toFixed(2)
        });
    });

    const formData = {
        type: 'non-gst',
        billToName: document.getElementById('nonGstBillToName').value,
        phone: phoneClean,
        email: document.getElementById('nonGstEmail').value,
        address: (document.getElementById('nonGstAddress') ? document.getElementById('nonGstAddress').value : ''),
        items: JSON.stringify(items),
        invoiceNo: invoiceNo || null,
        date: paymentDate
    };
    
    if (form.dataset.continueFrom) formData.continueFrom = form.dataset.continueFrom;
    if (form.dataset.originalTotalPayable) formData.originalTotalPayable = form.dataset.originalTotalPayable;
    if (form.dataset.cumulativeTotalPaid) formData.cumulativeTotalPaid = form.dataset.cumulativeTotalPaid;

    saveInvoice(formData);
    closeNonGstModal();
}

function saveInvoice(invoiceData) {
    fetch('api/save_invoice.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(invoiceData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (typeof loadInvoices === 'function') loadInvoices();
            if (typeof loadClients === 'function') loadClients();
            if (typeof loadTransactions === 'function') loadTransactions();
            if (typeof loadReceipts === 'function') loadReceipts();

            if (data.invoiceNo && data.isFullyPaid) {
                alert(`Invoice #${data.invoiceNo} generated successfully!`);
                if (typeof viewInvoiceByNo === 'function') viewInvoiceByNo(data.invoiceNo);
            } else if (data.receiptNo) {
                alert(`Bill created successfully with partial payment!\nReceipt No: ${data.receiptNo}\n\n(Official invoice number will be allocated once 100% payment is completed)`);
                window.open(`api/view_receipt.php?receiptNo=${encodeURIComponent(data.receiptNo)}`, '_blank');
            } else {
                alert('Bill created successfully!\n(Official invoice number will be allocated once 100% payment is completed)');
            }
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(err => {
        console.error('Error saving invoice:', err);
        alert('Network error saving invoice.');
    });
}

function autofillClientDetails(type) {
    const nameInput = document.getElementById(type === 'gst' ? 'gstBillToName' : 'nonGstBillToName');
    if (!nameInput) return;
    const name = nameInput.value.trim();
    if (!name) return;
    
    const match = (window.allClientsList || []).find(c => c.name.toLowerCase() === name.toLowerCase());
    if (match) {
        if (type === 'gst') {
            if (match.phone && document.getElementById('gstPhone')) document.getElementById('gstPhone').value = match.phone;
            if (match.email && match.email !== 'N/A' && document.getElementById('gstEmail')) document.getElementById('gstEmail').value = match.email;
            if (match.gst_number && match.gst_number !== 'N/A' && document.getElementById('gstModalNumber')) document.getElementById('gstModalNumber').value = match.gst_number;
        } else {
            if (match.phone && document.getElementById('nonGstPhone')) document.getElementById('nonGstPhone').value = match.phone;
            if (match.email && match.email !== 'N/A' && document.getElementById('nonGstEmail')) document.getElementById('nonGstEmail').value = match.email;
        }
    }
}

function checkExistingUser(phone, type) {
    // Optional lookup helper
}


// ==========================================
// VOUCHER MANAGEMENT FUNCTIONS
// ==========================================

function showAddVoucherModal() {
    if (document.getElementById('voucherDate')) {
        document.getElementById('voucherDate').value = new Date().toISOString().split('T')[0];
    }
    if (typeof populatePaymentModeSelects === 'function') {
        populatePaymentModeSelects();
    }
    const modal = document.getElementById('voucherModal');
    if (modal) {
        modal.classList.add('show');
    }
}

function closeVoucherModal() {
    const modal = document.getElementById('voucherModal');
    if (modal) {
        modal.classList.remove('show');
    }
    const form = document.getElementById('voucherForm');
    if (form) form.reset();
}

function generateVoucher(event) {
    if (event) event.preventDefault();
    const formData = {
        payee: document.getElementById('voucherPayee').value,
        amount: document.getElementById('voucherAmount').value,
        mode: document.getElementById('voucherMode').value,
        date: document.getElementById('voucherDate').value,
        description: document.getElementById('voucherDescription').value
    };
    fetch('api/save_voucher.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeVoucherModal();
            loadVouchers();
            window.open(`api/generate_voucher.php?refNo=${data.refNo}&${new URLSearchParams(formData).toString()}`, '_blank');
        } else {
            alert('Error generating voucher: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Error generating voucher:', err);
        alert('Network error saving voucher.');
    });
}

function loadVouchers() {
    const container = document.getElementById('voucher-list');
    if (!container) return;

    fetch('api/get_vouchers.php')
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = `<p style="padding: 20px; color: #ef4444; text-align: center;">Error loading vouchers: ${data.error}</p>`;
                return;
            }

            if (!data || data.length === 0) {
                container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No vouchers generated yet. Click "+ Generate New Voucher" to create one!</p>';
                if (document.getElementById('total-voucher-count')) document.getElementById('total-voucher-count').innerText = '0';
                if (document.getElementById('total-voucher-amount')) document.getElementById('total-voucher-amount').innerText = '₹0';
                return;
            }

            // Stats
            let totalAmt = 0;
            data.forEach(v => totalAmt += parseFloat(v.amount || 0));
            if (document.getElementById('total-voucher-count')) document.getElementById('total-voucher-count').innerText = data.length;
            if (document.getElementById('total-voucher-amount')) document.getElementById('total-voucher-amount').innerText = '₹' + totalAmt.toLocaleString('en-IN');

            let html = `<table>
                <thead>
                    <tr>
                        <th>Ref No</th>
                        <th>Payee Name</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Mode</th>
                        <th style="width: 80px;">Action</th>
                    </tr>
                </thead>
                <tbody>`;

            data.forEach(v => {
                const formattedDate = v.date ? new Date(v.date).toLocaleDateString('en-GB') : '-';
                const refNo = v.ref_no || v.voucher_no || ('PV-' + v.id);
                const payee = v.payee || v.payee_name || '-';
                const amt = parseFloat(v.amount || 0).toLocaleString('en-IN');
                const mode = v.mode || v.payment_mode || 'Cash';
                const desc = v.description || '';

                html += `<tr>
                    <td><strong>${refNo}</strong></td>
                    <td>${payee}</td>
                    <td>${formattedDate}</td>
                    <td><strong>₹${amt}</strong></td>
                    <td><span class="category-badge">${mode}</span></td>
                    <td>
                        <button class="btn-action" title="View Voucher" 
                            onclick="window.open('api/generate_voucher.php?refNo=${refNo}&payee=${encodeURIComponent(payee)}&amount=${v.amount}&mode=${encodeURIComponent(mode)}&date=${v.date}&description=${encodeURIComponent(desc)}', '_blank')"
                            style="background: #0ea5e9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                            👁️
                        </button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        })
        .catch(err => {
            console.error('Error fetching vouchers:', err);
            container.innerHTML = '<p style="padding: 20px; color: #ef4444; text-align: center;">Failed to load vouchers from server.</p>';
        });
}

window.loadVouchers = loadVouchers;
window.showAddVoucherModal = showAddVoucherModal;
window.closeVoucherModal = closeVoucherModal;
window.generateVoucher = generateVoucher;
