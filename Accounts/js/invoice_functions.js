// Invoice Management System
var generatedInvoices = [];
var userDatabase = [];

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

// Display invoices
function displayInvoices() {
    const container = document.getElementById('invoice-list');
    if (!container) return;
    
    if (!generatedInvoices || generatedInvoices.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No invoices generated yet. Click "Generate New Invoice" to create one!</p>';
        return;
    }
    
    let html = `<table>
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Type</th>
                <th>Purchaser</th>
                <th>Date Generated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;
    
    const sortedInvoices = [...generatedInvoices].sort((a, b) => {
        return new Date(b.generatedAt || b.date) - new Date(a.generatedAt || a.date);
    });

    sortedInvoices.forEach((invoice) => {
        const formattedDate = new Date(invoice.generatedAt || invoice.date).toLocaleDateString('en-GB');
        html += `<tr>
            <td><strong>${invoice.invoiceNo}</strong></td>
            <td><span class="category-badge">${invoice.type === 'gst' ? 'GST' : 'Non-GST'}</span></td>
            <td>${invoice.billToName || 'N/A'}</td>
            <td>${formattedDate}</td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-action" onclick="viewInvoiceByNo('${invoice.invoiceNo}')" title="View Invoice" style="background: #0ea5e9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        👁️
                    </button>
                    <button class="btn-action" onclick="copyInvoiceLink('${invoice.invoiceNo}')" title="Copy Shareable Link" style="background: #a855f7; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        🔗
                    </button>
                    <button class="btn-action" onclick="downloadInvoiceByNo('${invoice.invoiceNo}')" title="Download PDF" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        📥
                    </button>
                    <button class="btn-action" onclick="editInvoiceByNo('${invoice.invoiceNo}')" title="Edit Data" style="background: #64748b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                        ✏️
                    </button>
                    <button class="btn-action" onclick="deleteInvoiceByNo('${invoice.invoiceNo}')" title="Delete Invoice" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;

    // Update Stats Cards
    const totInvoices = generatedInvoices.length;
    let totColl = 0;
    let totPend = 0;
    
    // Group by student phone to avoid double-counting installments (P1, P2) in financial stats
    const studentLatest = {};
    generatedInvoices.forEach(inv => {
        const phone = inv.phone || inv.billToName; // Fallback if phone is missing
        if (!studentLatest[phone] || parseInt(inv.id) > parseInt(studentLatest[phone].id)) {
            studentLatest[phone] = inv;
        }
    });

    const totStudents = Object.keys(studentLatest).length;

    Object.values(studentLatest).forEach(inv => {
        const paid = parseFloat(inv.cumulativeTotalPaid || 0);
        const totalCharged = parseFloat(inv.originalTotalPayable || 5000);
        
        totColl += paid;
        const pending = totalCharged - paid;
        if (pending > 0) totPend += pending;
    });

    // Calculate Cash vs Online breakdown from Receipts
    let totCash = 0;
    let totOnline = 0;
    if (typeof generatedReceipts !== 'undefined' && Array.isArray(generatedReceipts)) {
        generatedReceipts.forEach(rec => {
            try {
                const items = JSON.parse(rec.items || '[]');
                if (Array.isArray(items)) {
                    let receiptPaid = 0;
                    let mode = 'online';
                    items.forEach(item => {
                        receiptPaid += parseFloat(item.paidAmt || item.amount || 0);
                        if (item.paymentMode) {
                            mode = item.paymentMode.toLowerCase();
                        }
                    });
                    if (mode === 'cash') {
                        totCash += receiptPaid;
                    } else {
                        totOnline += receiptPaid;
                    }
                }
            } catch (e) {
                console.error("Error parsing items for receipt", rec.receiptNo, e);
            }
        });
    }

    if (document.getElementById('total-invoice-count')) {
        document.getElementById('total-invoice-count').innerText = totInvoices;
    }
    
    // Calculate total amount billed
    let totBilled = 0;
    Object.values(studentLatest).forEach(inv => {
        totBilled += parseFloat(inv.originalTotalPayable || 0);
    });

    const amountEl = document.getElementById('total-invoice-amount');
    if (amountEl) {
        amountEl.innerText = '₹' + totBilled.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    
    const paidEl = document.getElementById('total-invoice-paid');
    if (paidEl) {
        paidEl.innerText = '₹' + totColl.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    const cashEl = document.getElementById('total-invoice-cash');
    if (cashEl) {
        cashEl.innerText = '₹' + totCash.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    const onlineEl = document.getElementById('total-invoice-online');
    if (onlineEl) {
        onlineEl.innerText = '₹' + totOnline.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    
    const pendingEl = document.getElementById('total-invoice-pending');
    if (pendingEl) {
        pendingEl.innerText = '₹' + totPend.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }

    // Also support legacy/alternative IDs if they exist
    const collectedEl = document.getElementById('total-invoice-collected');
    if (collectedEl) {
        collectedEl.innerText = '₹' + totColl.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
}

// Search Invoices
function searchInvoices() {
    const query = document.getElementById('invoice-search').value.toLowerCase();
    const rows = document.querySelectorAll('#invoice-list tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// View/Generate Invoice by No
function viewInvoiceByNo(invoiceNo) {
    if (!invoiceNo) return;
    const url = `api/view_invoice.php?invoiceNo=${encodeURIComponent(invoiceNo)}&t=${Date.now()}`;
    window.open(url, '_blank');
}

// Edit invoice by number
function editInvoiceByNo(invoiceNo) {
    const invoice = generatedInvoices.find(inv => inv.invoiceNo === invoiceNo);
    if (!invoice) {
        alert('Invoice data not found');
        return;
    }
    
    if (invoice.type === 'gst') {
        // Populate GST form
        document.getElementById('gstBillToName').value = invoice.billToName;
        document.getElementById('gstPhone').value = invoice.phone;
        document.getElementById('gstModalNumber').value = invoice.gstNumber;
        document.getElementById('gstEmail').value = invoice.email || '';
        document.getElementById('gstAddress').value = invoice.address || '';
        
        // Populate items
        const items = JSON.parse(invoice.items);
        const container = document.getElementById('gstItemsContainer');
        container.innerHTML = '';
        
        items.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'invoice-item-row-gst';
            row.style = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
            row.innerHTML = `
                <input type="text" class="form-input gst-desc" placeholder="Description" value="${item.description}" required style="flex: 3;">
                <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" value="${item.totalInclTax || item.amount}" step="0.01" min="0" required style="flex: 1;" oninput="onGstItemAmountChange()">
                <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                    <input type="checkbox" class="gst-desc-check" ${item.hasDesc ? 'checked' : ''}> Desc.%
                </label>
                <button type="button" class="btn-${idx === 0 ? 'add' : 'remove'}-item" onclick="${idx === 0 ? 'addGstItem()' : 'removeItem(this)'}">${idx === 0 ? '+' : '−'}</button>
            `;
            container.appendChild(row);
        });
        
        // Populate payment & summary fields
        if (items.length > 0) {
            document.getElementById('gstPaymentMode').value = items[0].paymentMode || '';
            document.getElementById('gstPaymentDate').value = items[0].date || '';
            let totalPaidAmt = 0;
            items.forEach(item => {
                totalPaidAmt += parseFloat(item.paidAmt || 0);
            });
            document.getElementById('gstAmountPaid').value = totalPaidAmt.toFixed(2);
        }
        
        // Show section and generate button
        document.getElementById('gstPaymentSummarySection').style.display = 'block';
        document.getElementById('btnGstGenerate').style.display = 'inline-block';
        
        // Update summary display
        updateGstSummary();
        
        document.getElementById('gstInvoiceForm').dataset.editInvoiceNo = invoiceNo;
        document.getElementById('gstInvoiceModal').classList.add('show');
    } else {
        // Populate Non-GST form
        document.getElementById('nonGstBillToName').value = invoice.billToName;
        document.getElementById('nonGstPhone').value = invoice.phone;
        document.getElementById('nonGstEmail').value = invoice.email || '';
        document.getElementById('nonGstAddress').value = invoice.address || '';
        
        const items = JSON.parse(invoice.items);
        const container = document.getElementById('nonGstItemsContainer');
        container.innerHTML = '';
        
        items.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'invoice-item-row';
            row.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
            row.innerHTML = `
                <input type="text" class="form-input nongst-item-desc" placeholder="Description" value="${item.description}" required style="flex: 3;">
                <input type="number" class="form-input nongst-item-amount" placeholder="Amount" value="${item.amount}" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
                <button type="button" class="btn-${idx === 0 ? 'add' : 'remove'}-item" onclick="${idx === 0 ? 'addNonGstItem()' : 'removeNonGstItem(this)'}">${idx === 0 ? '+' : '−'}</button>
            `;
            container.appendChild(row);
        });
        
        // Populate payment & summary fields
        if (items.length > 0) {
            document.getElementById('nonGstPaymentMode').value = items[0].paymentMode || '';
            document.getElementById('nonGstPaymentDate').value = items[0].date || '';
            let totalPaidAmt = 0;
            items.forEach(item => {
                totalPaidAmt += parseFloat(item.paidAmt || 0);
            });
            document.getElementById('nonGstAmountPaid').value = totalPaidAmt.toFixed(2);
        }
        
        // Show section and generate button
        document.getElementById('nonGstPaymentSummarySection').style.display = 'block';
        document.getElementById('btnNonGstGenerate').style.display = 'inline-block';
        
        // Update summary display
        updateNonGstSummary();
        
        document.getElementById('nonGstInvoiceForm').dataset.editInvoiceNo = invoiceNo;
        document.getElementById('nonGstInvoiceModal').classList.add('show');
    }
}

// Check for existing user by phone
function checkExistingUser(phone, type) {
    if (phone.length < 10) return;

    fetch(`api/get_customer_invoices.php?phone=${encodeURIComponent(phone)}&type=${type}`)
        .then(response => response.json())
        .then(existingInvoices => {
            const container = document.getElementById(type === 'gst' ? 'existingUserGst' : 'existingUserNonGst');
            if (!container) return;
            
            if (existingInvoices && existingInvoices.length > 0) {
                const lastInvoice = existingInvoices[existingInvoices.length - 1];
                
                // Group invoices by base invoice number to track dues separately
                const baseInvoiceGroups = {};
                existingInvoices.forEach(inv => {
                    let baseNo = inv.invoiceNo;
                    if (inv.invoiceNo.includes('/P')) {
                        baseNo = inv.invoiceNo.split('/P')[0];
                    }
                    
                    if (!baseInvoiceGroups[baseNo]) {
                        baseInvoiceGroups[baseNo] = {
                            baseInvoiceNo: baseNo,
                            lastInvoiceNo: inv.invoiceNo,
                            totalPayable: 0,
                            totalPaid: 0,
                            billToName: inv.billToName,
                            gstNumber: inv.gstNumber || '',
                            email: inv.email || '',
                            address: inv.address || ''
                        };
                    }
                    
                    const invItems = JSON.parse(inv.items || '[]');
                    if (!inv.invoiceNo.includes('/P') || inv.invoiceNo.endsWith('/P1')) {
                        invItems.forEach(item => {
                            baseInvoiceGroups[baseNo].totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                        });
                    }
                    invItems.forEach(item => {
                        baseInvoiceGroups[baseNo].totalPaid += parseFloat(item.paidAmt || item.amount || 0);
                    });
                    
                    baseInvoiceGroups[baseNo].lastInvoiceNo = inv.invoiceNo;
                });
                
                // Find all groups with active balance due
                const activeDues = [];
                Object.keys(baseInvoiceGroups).forEach(baseNo => {
                    const group = baseInvoiceGroups[baseNo];
                    const due = group.totalPayable - group.totalPaid;
                    if (due > 0.01) {
                        activeDues.push({
                            baseInvoiceNo: baseNo,
                            lastInvoiceNo: group.lastInvoiceNo,
                            dueAmount: due
                        });
                    }
                });
                
                let html = `
                    <div style="background: #e0f2fe; border: 1px solid #0284c7; padding: 12px; border-radius: 6px; margin-top: 10px; font-size: 13px; color: #0369a1; text-align: left; line-height: 1.5;">
                        <strong>Existing Customer Found!</strong><br>
                        Name: ${lastInvoice.billToName}<br>
                        ${lastInvoice.gstNumber ? 'GST: ' + lastInvoice.gstNumber + '<br>' : ''}
                        Previous Invoices: ${existingInvoices.length}<br>`;
                
                if (activeDues.length > 0) {
                    html += `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #0284c7;">`;
                    html += `<strong style="color: #0369a1;">Select an Outstanding Due to Continue Payment:</strong><br>`;
                    activeDues.forEach(dueItem => {
                        html += `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px; background: #ffffff; padding: 6px 8px; border-radius: 4px; border: 1px solid #bae6fd; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div>
                                    <span style="font-weight: 600; color: #1e293b;">Invoice: ${dueItem.baseInvoiceNo}</span><br>
                                    <span style="font-size: 11px; color: #ef4444; font-weight: 500;">Due: ₹${dueItem.dueAmount.toFixed(2)}</span>
                                </div>
                                <button type="button" class="btn-primary" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;" onclick="continuePartPayment('${phone}', '${type}', '${dueItem.lastInvoiceNo}')">Pay Due</button>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    
                    html += `
                        <div style="margin-top: 12px; display: flex; gap: 8px;">
                            <button type="button" class="btn-primary" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 12px;" onclick="loadExistingCustomer('${phone}', '${type}')">Create Fresh Invoice</button>
                        </div>
                    `;
                } else {
                    html += `
                        <div style="margin-top: 10px;">
                            <button type="button" class="btn-primary" style="background: #0ea5e9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 12px;" onclick="loadExistingCustomer('${phone}', '${type}')">Load Customer Data</button>
                        </div>
                    `;
                }
                
                html += `</div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error checking user:', error);
            const container = document.getElementById(type === 'gst' ? 'existingUserGst' : 'existingUserNonGst');
            if (container) container.innerHTML = '';
        });
}

// Load existing customer data
function loadExistingCustomer(phone, type) {
    fetch(`api/get_customer_invoices.php?phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(existingInvoices => {
            if (!existingInvoices || existingInvoices.length === 0) return;
            
            const lastInvoice = existingInvoices[existingInvoices.length - 1];
            
            if (type === 'gst') {
                document.getElementById('gstBillToName').value = lastInvoice.billToName;
                document.getElementById('gstModalNumber').value = lastInvoice.gstNumber || '';
                document.getElementById('gstEmail').value = lastInvoice.email || '';
                
                const form = document.getElementById('gstInvoiceForm');
                delete form.dataset.continueFrom;
                delete form.dataset.originalTotalPayable;
                delete form.dataset.cumulativeTotalPaid;
                
                const container = document.getElementById('gstItemsContainer');
                container.innerHTML = `
                    <div class="invoice-item-row-gst" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
                        <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstItemAmountChange()">
                        <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                            <input type="checkbox" class="gst-desc-check"> Desc.%
                        </label>
                        <button type="button" class="btn-add-item" onclick="addGstItem()">+</button>
                    </div>
                `;
                document.getElementById('gstPaymentSummarySection').style.display = 'none';
                document.getElementById('btnGstGenerate').style.display = 'none';
            } else {
                document.getElementById('nonGstBillToName').value = lastInvoice.billToName;
                document.getElementById('nonGstEmail').value = lastInvoice.email || '';
                document.getElementById('nonGstAddress').value = lastInvoice.address || '';
                
                const form = document.getElementById('nonGstInvoiceForm');
                delete form.dataset.continueFrom;
                delete form.dataset.originalTotalPayable;
                delete form.dataset.cumulativeTotalPaid;
                
                const container = document.getElementById('nonGstItemsContainer');
                container.innerHTML = `
                    <div class="invoice-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
                        <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
                        <button type="button" class="btn-add-item" onclick="addNonGstItem()">+</button>
                    </div>
                `;
                document.getElementById('nonGstPaymentSummarySection').style.display = 'none';
                document.getElementById('btnNonGstGenerate').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading customer:', error);
        });
}

// Continue part payment
function continuePartPayment(phone, type, targetLastInvoiceNo) {
    fetch(`api/get_customer_invoices.php?phone=${encodeURIComponent(phone)}&type=${type}`)
        .then(response => response.json())
        .then(existingInvoices => {
            if (existingInvoices.length === 0) return;
            
            // Extract the base invoice of targetLastInvoiceNo
            let targetBaseNo = targetLastInvoiceNo;
            if (targetLastInvoiceNo.includes('/P')) {
                targetBaseNo = targetLastInvoiceNo.split('/P')[0];
            }
            
            let totalPayable = 0;
            let totalPaid = 0;
            let lastInvoiceRecord = null;
            
            existingInvoices.forEach(inv => {
                let invBase = inv.invoiceNo;
                if (inv.invoiceNo.includes('/P')) {
                    invBase = inv.invoiceNo.split('/P')[0];
                }
                
                if (invBase === targetBaseNo) {
                    lastInvoiceRecord = inv;
                    const items = JSON.parse(inv.items || '[]');
                    
                    if (!inv.invoiceNo.includes('/P') || inv.invoiceNo.endsWith('/P1')) {
                        items.forEach(item => {
                            totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                        });
                    }
                    
                    items.forEach(item => {
                        totalPaid += parseFloat(item.paidAmt || item.amount || 0);
                    });
                }
            });
            
            if (!lastInvoiceRecord) return;
            
            const balanceDue = totalPayable - totalPaid;
            
            if (type === 'gst') {
                document.getElementById('gstBillToName').value = lastInvoiceRecord.billToName;
                document.getElementById('gstModalNumber').value = lastInvoiceRecord.gstNumber || '';
                document.getElementById('gstEmail').value = lastInvoiceRecord.email || '';
                document.getElementById('gstAddress').value = lastInvoiceRecord.address || '';
                
                const container = document.getElementById('gstItemsContainer');
                container.innerHTML = '';
                
                const today = new Date().toISOString().split('T')[0];
                const newRow = document.createElement('div');
                newRow.className = 'invoice-item-row-gst';
                newRow.style = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
                newRow.innerHTML = `
                    <input type="text" class="form-input gst-desc" placeholder="Payment Description" value="Part Payment" required style="flex: 3;">
                    <input type="number" class="form-input gst-total-incl" placeholder="Balance Due" value="${balanceDue.toFixed(2)}" step="0.01" min="0" readonly style="flex: 1; background: #f1f5f9;" oninput="onGstItemAmountChange()">
                    <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                        <input type="checkbox" class="gst-desc-check"> Desc.%
                    </label>
                    <button type="button" class="btn-add-item" onclick="addGstItem()">+</button>
                `;
                container.appendChild(newRow);
                
                document.getElementById('gstPaymentDate').value = today;
                document.getElementById('gstAmountPaid').value = balanceDue.toFixed(2);
                
                const form = document.getElementById('gstInvoiceForm');
                form.dataset.continueFrom = targetLastInvoiceNo;
                form.dataset.originalTotalPayable = totalPayable.toFixed(2);
                form.dataset.cumulativeTotalPaid = totalPaid.toFixed(2);
                
                document.getElementById('gstPaymentSummarySection').style.display = 'block';
                document.getElementById('btnGstGenerate').style.display = 'inline-block';
                
                updateGstSummary();
            } else {
                document.getElementById('nonGstBillToName').value = lastInvoiceRecord.billToName;
                document.getElementById('nonGstEmail').value = lastInvoiceRecord.email || '';
                document.getElementById('nonGstAddress').value = lastInvoiceRecord.address || '';
                
                const container = document.getElementById('nonGstItemsContainer');
                container.innerHTML = '';
                
                const today = new Date().toISOString().split('T')[0];
                const newRow = document.createElement('div');
                newRow.className = 'invoice-item-row';
                newRow.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
                newRow.innerHTML = `
                    <input type="text" class="form-input nongst-item-desc" placeholder="Description" value="Part Payment" required style="flex: 3;">
                    <input type="number" class="form-input nongst-item-amount" placeholder="Amount" value="${balanceDue.toFixed(2)}" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstItemAmountChange()">
                    <button type="button" class="btn-add-item" onclick="addNonGstItem()">+</button>
                `;
                container.appendChild(newRow);
                
                document.getElementById('nonGstPaymentDate').value = today;
                document.getElementById('nonGstAmountPaid').value = balanceDue.toFixed(2);
                
                const form = document.getElementById('nonGstInvoiceForm');
                form.dataset.continueFrom = targetLastInvoiceNo;
                form.dataset.originalTotalPayable = totalPayable.toFixed(2);
                form.dataset.cumulativeTotalPaid = totalPaid.toFixed(2);
                
                document.getElementById('nonGstPaymentSummarySection').style.display = 'block';
                document.getElementById('btnNonGstGenerate').style.display = 'inline-block';
                
                updateNonGstSummary();
            }
        })
        .catch(error => {
            console.error('Error continuing part payment:', error);
            alert('Failed to load customer data. Please try again.');
        });
}

// Modal Helpers
function showInvoiceTypeSelection() {
    const div = document.getElementById('invoiceTypeSelectionDiv');
    div.style.display = div.style.display === 'none' ? 'grid' : 'none';
}

function selectInvoiceType(type) {
    document.getElementById('invoiceTypeSelectionDiv').style.display = 'none';
    const today = new Date().toISOString().split('T')[0];
    if (type === 'non-gst') {
        document.getElementById('nonGstPaymentDate').value = today;
        document.getElementById('nonGstInvoiceModal').classList.add('show');
    } else if (type === 'gst') {
        document.getElementById('gstPaymentDate').value = today;
        document.getElementById('gstInvoiceModal').classList.add('show');
    }
}

function closeGstModal() {
    document.getElementById('gstInvoiceModal').classList.remove('show');
    document.getElementById('gstInvoiceForm').reset();
    
    const form = document.getElementById('gstInvoiceForm');
    delete form.dataset.editInvoiceNo;
    delete form.dataset.continueFrom;
    delete form.dataset.originalTotalPayable;
    delete form.dataset.cumulativeTotalPaid;
    
    document.getElementById('gstPaymentSummarySection').style.display = 'none';
    document.getElementById('btnGstGenerate').style.display = 'none';
    
    const container = document.getElementById('gstItemsContainer');
    container.innerHTML = `
        <div class="invoice-item-row-gst" style="display: flex; gap: 8px; margin-bottom: 8px;">
            <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
            <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstItemAmountChange()">
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

// Item row management
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
        <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstItemAmountChange()">
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

// Done Action for GST
function clickGstDone() {
    const billToName = document.getElementById('gstBillToName');
    const phone = document.getElementById('gstPhone');
    const gstNumber = document.getElementById('gstModalNumber');
    
    if (!billToName.value.trim()) {
        billToName.reportValidity();
        return;
    }
    if (!phone.value.trim()) {
        phone.reportValidity();
        return;
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
    
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.gst-total-incl');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const amountPaidInput = document.getElementById('gstAmountPaid');
    if (!amountPaidInput.value) {
        amountPaidInput.value = totalItemsAmt.toFixed(2);
    }
    
    document.getElementById('gstPaymentSummarySection').style.display = 'block';
    document.getElementById('btnGstGenerate').style.display = 'inline-block';
    
    updateGstSummary();
    
    document.getElementById('gstPaymentSummarySection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Done Action for Non-GST
function clickNonGstDone() {
    const billToName = document.getElementById('nonGstBillToName');
    const phone = document.getElementById('nonGstPhone');
    
    if (!billToName.value.trim()) {
        billToName.reportValidity();
        return;
    }
    if (!phone.value.trim()) {
        phone.reportValidity();
        return;
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
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.gst-total-incl');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const form = document.getElementById('gstInvoiceForm');
    
    let totalPayable = totalItemsAmt;
    let prevPaid = 0;
    
    if (form.dataset.continueFrom) {
        totalPayable = parseFloat(form.dataset.originalTotalPayable) || totalItemsAmt;
        prevPaid = parseFloat(form.dataset.cumulativeTotalPaid) || 0;
    }
    
    const exclTax = totalPayable / 1.18;
    const gstAmt = totalPayable - exclTax;
    
    const amountPaid = parseFloat(document.getElementById('gstAmountPaid').value) || 0;
    const totalPaidSoFar = prevPaid + amountPaid;
    const balanceDue = Math.max(0, totalPayable - totalPaidSoFar);
    
    document.getElementById('summaryGstCharges').innerText = '₹' + exclTax.toFixed(2);
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

// Save logic
async function generateGstInvoice(event) {
    if (event) event.preventDefault();
    const invoiceNo = document.getElementById('gstInvoiceForm').dataset.editInvoiceNo;
    
    const paymentMode = document.getElementById('gstPaymentMode').value;
    const paymentDate = document.getElementById('gstPaymentDate').value;
    const amountPaid = parseFloat(document.getElementById('gstAmountPaid').value) || 0;
    
    if (!paymentMode) {
        alert('Please select a payment mode.');
        return;
    }
    
    // Calculate total sum of items
    let totalItemsAmt = 0;
    document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst').forEach(row => {
        const amtVal = parseFloat(row.querySelector('.gst-total-incl').value) || 0;
        totalItemsAmt += amtVal;
    });
    
    const ratio = totalItemsAmt > 0 ? (amountPaid / totalItemsAmt) : 0;
    
    const items = [];
    const rows = document.querySelectorAll('#gstItemsContainer .invoice-item-row-gst');
    rows.forEach((row, index) => {
        const desc = row.querySelector('.gst-desc').value;
        const totalIncl = parseFloat(row.querySelector('.gst-total-incl').value) || 0;
        const hasDesc = row.querySelector('.gst-desc-check').checked;
        
        const charges = totalIncl / 1.18;
        const gst = totalIncl - charges;
        
        let itemPaid = totalIncl * ratio;
        // Adjust the last item to avoid rounding issues
        if (index === rows.length - 1) {
            let sumPaidPrev = 0;
            items.forEach(itm => sumPaidPrev += parseFloat(itm.paidAmt));
            itemPaid = amountPaid - sumPaidPrev;
        }
        
        items.push({
            description: desc,
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
        phone: document.getElementById('gstPhone').value,
        gstNumber: document.getElementById('gstModalNumber').value,
        email: document.getElementById('gstEmail').value,
        address: document.getElementById('gstAddress').value,
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
        phone: document.getElementById('nonGstPhone').value,
        email: document.getElementById('nonGstEmail').value,
        address: document.getElementById('nonGstAddress').value || '',
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
            loadInvoicesFromDB();
            loadCustomersFromDB();
            viewInvoiceByNo(data.invoiceNo);
        } else {
            alert('Error: ' + data.error);
        }
    });
}

function loadInvoicesFromDB() {
    Promise.all([
        fetch('api/get_invoices.php').then(r => r.json()),
        fetch('api/get_receipts.php').then(r => r.json())
    ])
    .then(([invData, recData]) => {
        generatedInvoices = Array.isArray(invData) ? invData : [];
        generatedReceipts = Array.isArray(recData) ? recData : [];
        displayInvoices();
    })
    .catch(err => {
        console.error('Error loading invoices and receipts:', err);
        generatedInvoices = [];
        generatedReceipts = [];
        displayInvoices();
    });
}

function loadCustomersFromDB() {
    fetch('api/get_customers.php')
        .then(r => r.json())
        .then(data => {
            if (!Array.isArray(data)) {
                console.error('Expected array from get_customers.php, got:', data);
                return;
            }
            userDatabase = data.map(u => ({
                name: u.name,
                phone: u.phone,
                email: u.email || 'N/A',
                gstNumber: u.gstNumber || 'N/A',
                invoiceCount: u.invoiceCount || 0,
                lastInvoiceDate: u.lastInvoiceDate || 'N/A'
            }));
            displayUserInfo();
        })
        .catch(err => {
            console.error('Error in loadCustomersFromDB:', err);
        });
}

function displayUserInfo() {
    const container = document.getElementById('user-info-list');
    if (!container) return;
    let html = `<table><thead><tr><th>Name</th><th>Phone</th><th>GST</th><th>Last Date</th></tr></thead><tbody>`;
    userDatabase.forEach(u => {
        html += `<tr><td>${u.name}</td><td>${u.phone}</td><td>${u.gstNumber}</td><td>${u.lastInvoiceDate}</td></tr>`;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Voucher Logic
function showAddVoucherModal() {
    document.getElementById('voucherDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('voucherModal').classList.add('show');
}

function closeVoucherModal() {
    document.getElementById('voucherModal').classList.remove('show');
    document.getElementById('voucherForm').reset();
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
    }).then(r => r.json()).then(data => {
        if (data.success) {
            closeVoucherModal();
            loadVouchers();
            window.open(`api/generate_voucher.php?refNo=${data.refNo}&${new URLSearchParams(formData).toString()}`, '_blank');
        }
    });
}

function loadVouchers() {
    const container = document.getElementById('voucher-list');
    if (!container) return;

    fetch('api/get_vouchers.php')
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                container.innerHTML = `<p style="padding: 20px; color: #ef4444;">Error: ${data.error}</p>`;
                return;
            }

            if (!data || data.length === 0) {
                container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No vouchers generated yet. Click "+ Generate New Voucher" to create one!</p>';
                document.getElementById('total-voucher-count').innerText = '0';
                document.getElementById('total-voucher-amount').innerText = '₹0';
                return;
            }

            // Stats
            let totalAmt = 0;
            data.forEach(v => totalAmt += parseFloat(v.amount));
            document.getElementById('total-voucher-count').innerText = data.length;
            document.getElementById('total-voucher-amount').innerText = '₹' + totalAmt.toLocaleString('en-IN');

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
                const formattedDate = new Date(v.date).toLocaleDateString('en-GB');
                html += `<tr>
                    <td><strong>${v.ref_no}</strong></td>
                    <td>${v.payee}</td>
                    <td>${formattedDate}</td>
                    <td><strong>₹${parseFloat(v.amount).toLocaleString('en-IN')}</strong></td>
                    <td><span class="category-badge">${v.mode}</span></td>
                    <td>
                        <button class="btn-action" title="View Voucher" 
                            onclick="window.open('api/generate_voucher.php?refNo=${v.ref_no}&payee=${encodeURIComponent(v.payee)}&amount=${v.amount}&mode=${v.mode}&date=${v.date}&description=${encodeURIComponent(v.description)}', '_blank')"
                            style="background: #0ea5e9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                            👁️
                        </button>
                    </td>
                </tr>`;
            });

            html += '</tbody></table>';
            container.innerHTML = html;
        });
}

// Payslip Logic
function showAddPayslipModal() { document.getElementById('payslipModal').classList.add('show'); }
function closePayslipModal() { document.getElementById('payslipModal').classList.remove('show'); }

async function loadPayslips() {
    const container = document.getElementById('payslip-list');
    if (!container) return;
    fetch('api/get_payslips.php').then(r => r.json()).then(data => {
        if (!data.success) return;
        let html = `<table><thead><tr><th>Ref</th><th>Name</th><th>Month</th><th>Action</th></tr></thead><tbody>`;
        data.payslips.forEach(p => {
            html += `<tr><td>${p.ref_no}</td><td>${p.employee_name}</td><td>${p.month_year}</td><td><button onclick="window.open('api/generate_payslip.php?refNo=${p.ref_no}', '_blank')">👁️</button></td></tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    });
}

// Bulk Actions
async function handleBulkUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    // Show loading state
    const btn = document.querySelector('button[onclick*="bulkInvoiceCsv"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '🕒 Processing...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('csvFile', file);

    fetch('api/bulk_invoice_process.php', { method: 'POST', body: formData })
    .then(r => {
        if (!r.ok) throw new Error('Server error');
        return r.json();
    })
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        event.target.value = ''; // Reset file input

        if (data.success) {
            alert('Successfully generated ' + data.count + ' invoices!');
            loadInvoicesFromDB();
            loadCustomersFromDB();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        console.error('Bulk upload error:', err);
        alert('Failed to process bulk upload. Please check your CSV format.');
    });
}

async function clearAllInvoiceData() {
    if (confirm('Delete ALL data?')) {
        fetch('api/clear_all_invoices.php', { method: 'POST' }).then(() => {
            loadInvoicesFromDB();
            loadCustomersFromDB();
        });
    }
}

async function downloadAllInvoicesZip() {
    const btn = document.querySelector('button[onclick="downloadAllInvoicesZip()"]');
    const originalText = btn ? btn.innerHTML : '📥 Download All (Direct PDF)';
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '🕒 Initializing...';
    }

    try {
        // Step 1: Get list of invoices
        const response = await fetch('api/get_invoice_list.php');
        const invoices = await response.json();

        if (!Array.isArray(invoices) || invoices.length === 0) {
            alert('No invoices found to download.');
            return;
        }

        // Inform user
        alert(`Starting download for ${invoices.length} invoices. \nThis may take a minute. Please do not close the tab.`);

        // Step 2: Download each sequentially
        for (let i = 0; i < invoices.length; i++) {
            const invNo = invoices[i];
            const currentCount = i + 1;
            
            if (btn) btn.innerHTML = `⬇️ Downloading ${currentCount}/${invoices.length}...`;

            try {
                // Fetch the PDF as a Blob (Binary)
                const pdfResponse = await fetch(`api/download_invoice_pdf.php?invoiceNo=${encodeURIComponent(invNo)}`);
                if (!pdfResponse.ok) throw new Error(`HTTP ${pdfResponse.status}`);
                
                const blob = await pdfResponse.blob();
                const url = window.URL.createObjectURL(blob);
                
                // Create a temporary link and click it
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `Invoice_${invNo.replace(/[^A-Za-z0-9_-]/g, '_')}.pdf`;
                document.body.appendChild(a);
                a.click();
                
                // Cleanup
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
            } catch (err) {
                console.error(`Failed to download invoice ${invNo}:`, err);
            }

            // Small delay to prevent rate-limiting or network congestion
            await new Promise(resolve => setTimeout(resolve, 800));
        }

        if (btn) btn.innerHTML = '✅ All Invoices Downloaded!';
        setTimeout(() => {
            if (btn) btn.innerHTML = originalText;
        }, 5000);

    } catch (error) {
        console.error('Master download error:', error);
        alert('Could not start batch download. Please check your connection.');
        if (btn) btn.innerHTML = originalText;
    } finally {
        if (btn) btn.disabled = false;
    }
}

// Deletion logic for invoices
function deleteInvoiceByNo(invoiceNo) {
    if (!confirm(`Are you sure you want to delete invoice ${invoiceNo}? This cannot be undone.`)) return;

    fetch('api/delete_invoice.php?invoiceNo=' + encodeURIComponent(invoiceNo))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Refresh data WITHOUT page reload
                loadInvoicesFromDB();
                loadDashboardData(); 
            } else {
                alert('Error: ' + data.error);
            }
        });
}

// Download individual invoice PDF
function downloadInvoiceByNo(invoiceNo) {
    window.open('api/download_invoice_pdf.php?invoiceNo=' + encodeURIComponent(invoiceNo), '_blank');
}

// Copy shareable invoice link to clipboard
function copyInvoiceLink(invoiceNo) {
    if (!invoiceNo) return;
    const url = window.location.origin + window.location.pathname.replace('index.php', '') + 'api/view_invoice.php?invoiceNo=' + encodeURIComponent(invoiceNo);
    navigator.clipboard.writeText(url).then(() => {
        alert('Shareable invoice link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy text: ', err);
        // Fallback
        const el = document.createElement('textarea');
        el.value = url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert('Shareable invoice link copied to clipboard!');
    });
}

