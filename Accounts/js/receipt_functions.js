// Receipt Management System
var generatedReceipts = [];

// Initialize: Set today's date for initial receipt items
function setInitialReceiptDates() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('.gst-receipt-date, #nonGstReceiptItemsContainer input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
}

// Display receipts
function displayReceipts() {
    const container = document.getElementById('receipt-list');
    if (!container) return;
    
    if (!generatedReceipts || generatedReceipts.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No receipts generated yet. Click "Generate Receipt" to create one!</p>';
        return;
    }
    
    let html = `<table>
        <thead>
            <tr>
                <th>Receipt No</th>
                <th>Type</th>
                <th>Purchaser</th>
                <th>Date Generated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;
    
    const sortedReceipts = [...generatedReceipts].sort((a, b) => {
        return new Date(b.generatedAt || b.date) - new Date(a.generatedAt || a.date);
    });

    sortedReceipts.forEach((receipt) => {
        const formattedDate = new Date(receipt.generatedAt || receipt.date).toLocaleDateString('en-GB');
        html += `<tr>
            <td><strong>${receipt.receiptNo}</strong></td>
            <td><span class="category-badge">${receipt.type === 'gst' ? 'GST' : 'Non-GST'}</span></td>
            <td>${receipt.billToName || 'N/A'}</td>
            <td>${formattedDate}</td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <button class="btn-action" onclick="viewReceiptByNo('${receipt.receiptNo}')" title="View Receipt" style="background: #0ea5e9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        👁️
                    </button>
                    <button class="btn-action" onclick="downloadReceiptByNo('${receipt.receiptNo}')" title="Download PDF" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;">
                        📥
                    </button>
                    <button class="btn-action" onclick="editReceiptByNo('${receipt.receiptNo}')" title="Edit Data" style="background: #64748b; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                        ✏️
                    </button>
                    <button class="btn-action" onclick="deleteReceiptByNo('${receipt.receiptNo}')" title="Delete Receipt" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;

    // Update Stats Cards
    const totReceipts = generatedReceipts.length;
    let totColl = 0;
    let totPend = 0;
    
    // Group by student phone to avoid double-counting installments (P1, P2) in financial stats
    const studentLatest = {};
    generatedReceipts.forEach(rec => {
        const phone = rec.phone || rec.billToName; // Fallback if phone is missing
        if (!studentLatest[phone] || parseInt(rec.id) > parseInt(studentLatest[phone].id)) {
            studentLatest[phone] = rec;
        }
    });

    const totStudents = Object.keys(studentLatest).length;

    Object.values(studentLatest).forEach(rec => {
        const paid = parseFloat(rec.cumulativeTotalPaid || 0);
        const totalCharged = parseFloat(rec.originalTotalPayable || 5000);
        
        totColl += paid;
        const pending = totalCharged - paid;
        if (pending > 0) totPend += pending;
    });

    if (document.getElementById('total-receipt-count')) {
        document.getElementById('total-receipt-count').innerText = totReceipts;
    }
    
    // Calculate total amount billed
    let totBilled = 0;
    Object.values(studentLatest).forEach(rec => {
        totBilled += parseFloat(rec.originalTotalPayable || 0);
    });

    const amountEl = document.getElementById('total-receipt-amount');
    if (amountEl) {
        amountEl.innerText = '₹' + totBilled.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
    
    const paidEl = document.getElementById('total-receipt-paid');
    if (paidEl) {
        paidEl.innerText = '₹' + totColl.toLocaleString('en-IN', { minimumFractionDigits: 2 });
    }
}

// View/Generate Receipt by No
function viewReceiptByNo(receiptNo) {
    if (!receiptNo) return;
    const url = `api/view_receipt.php?receiptNo=${encodeURIComponent(receiptNo)}&t=${Date.now()}`;
    window.open(url, '_blank');
}

// Edit receipt by number
function editReceiptByNo(receiptNo) {
    const receipt = generatedReceipts.find(rec => rec.receiptNo === receiptNo);
    if (!receipt) {
        alert('Receipt data not found');
        return;
    }
    
    if (receipt.type === 'gst') {
        // Populate GST form
        document.getElementById('gstReceiptBillToName').value = receipt.billToName;
        document.getElementById('gstReceiptPhone').value = receipt.phone;
        document.getElementById('gstReceiptModalNumber').value = receipt.gstNumber || '';
        document.getElementById('gstReceiptEmail').value = receipt.email || '';
        document.getElementById('gstReceiptAddress').value = receipt.address || '';
        
        // Populate items
        const items = JSON.parse(receipt.items);
        const container = document.getElementById('gstReceiptItemsContainer');
        container.innerHTML = '';
        
        items.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'invoice-item-row-gst';
            row.style = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
            row.innerHTML = `
                <input type="text" class="form-input gst-desc" placeholder="Description" value="${item.description}" required style="flex: 3;">
                <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" value="${item.totalInclTax || item.amount}" step="0.01" min="0" required style="flex: 1;" oninput="onGstReceiptItemAmountChange()">
                <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                    <input type="checkbox" class="gst-desc-check" ${item.hasDesc ? 'checked' : ''}> Desc.%
                </label>
                <button type="button" class="btn-${idx === 0 ? 'add' : 'remove'}-item" onclick="${idx === 0 ? 'addGstReceiptItem()' : 'removeGstReceiptItem(this)'}">${idx === 0 ? '+' : '−'}</button>
            `;
            container.appendChild(row);
        });
        
        // Populate payment & summary fields
        if (items.length > 0) {
            const rawMode = items[0].paymentMode || '';
            const normalizedMode = rawMode ? (rawMode.charAt(0).toUpperCase() + rawMode.slice(1).toLowerCase()) : '';
            document.getElementById('gstReceiptPaymentMode').value = normalizedMode;
            document.getElementById('gstReceiptPaymentDate').value = items[0].date || '';
            let totalPaidAmt = 0;
            items.forEach(item => {
                totalPaidAmt += parseFloat(item.paidAmt || 0);
            });
            document.getElementById('gstReceiptAmountPaid').value = totalPaidAmt.toFixed(2);
        }
        
        // Show section and generate button
        document.getElementById('gstReceiptPaymentSummarySection').style.display = 'block';
        document.getElementById('btnGstReceiptGenerate').style.display = 'inline-block';
        
        // Update summary display
        updateGstReceiptSummary();
        
        document.getElementById('gstReceiptForm').dataset.editReceiptNo = receiptNo;
        document.getElementById('gstReceiptModal').classList.add('show');
    } else {
        // Populate Non-GST form
        document.getElementById('nonGstReceiptBillToName').value = receipt.billToName;
        document.getElementById('nonGstReceiptPhone').value = receipt.phone;
        document.getElementById('nonGstReceiptEmail').value = receipt.email || '';
        document.getElementById('nonGstReceiptAddress').value = receipt.address || '';
        
        const items = JSON.parse(receipt.items);
        const container = document.getElementById('nonGstReceiptItemsContainer');
        container.innerHTML = '';
        
        items.forEach((item, idx) => {
            const row = document.createElement('div');
            row.className = 'invoice-item-row';
            row.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
            row.innerHTML = `
                <input type="text" class="form-input nongst-item-desc" placeholder="Description" value="${item.description}" required style="flex: 3;">
                <input type="number" class="form-input nongst-item-amount" placeholder="Amount" value="${item.amount}" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstReceiptItemAmountChange()">
                <button type="button" class="btn-${idx === 0 ? 'add' : 'remove'}-item" onclick="${idx === 0 ? 'addNonGstReceiptItem()' : 'removeNonGstReceiptItem(this)'}">${idx === 0 ? '+' : '−'}</button>
            `;
            container.appendChild(row);
        });
        
        // Populate payment & summary fields
        if (items.length > 0) {
            const rawMode = items[0].paymentMode || '';
            const normalizedMode = rawMode ? (rawMode.charAt(0).toUpperCase() + rawMode.slice(1).toLowerCase()) : '';
            document.getElementById('nonGstReceiptPaymentMode').value = normalizedMode;
            document.getElementById('nonGstReceiptPaymentDate').value = items[0].date || '';
            let totalPaidAmt = 0;
            items.forEach(item => {
                totalPaidAmt += parseFloat(item.paidAmt || 0);
            });
            document.getElementById('nonGstReceiptAmountPaid').value = totalPaidAmt.toFixed(2);
        }
        
        // Show section and generate button
        document.getElementById('nonGstReceiptPaymentSummarySection').style.display = 'block';
        document.getElementById('btnNonGstReceiptGenerate').style.display = 'inline-block';
        
        // Update summary display
        updateNonGstReceiptSummary();
        
        document.getElementById('nonGstReceiptForm').dataset.editReceiptNo = receiptNo;
        document.getElementById('nonGstReceiptModal').classList.add('show');
    }
}

// Check for existing user by phone for Receipts
function checkExistingUserReceipt(phone, type) {
    if (phone.length < 10) return;

    fetch(`api/get_customer_receipts.php?phone=${encodeURIComponent(phone)}&type=${type}`)
        .then(response => response.json())
        .then(existingReceipts => {
            const container = document.getElementById(type === 'gst' ? 'existingUserGstReceipt' : 'existingUserNonGstReceipt');
            if (!container) return;
            
            if (existingReceipts && existingReceipts.length > 0) {
                const lastReceipt = existingReceipts[existingReceipts.length - 1];
                
                // Group receipts by base receipt number to track dues separately
                const baseReceiptGroups = {};
                existingReceipts.forEach(rec => {
                    let baseNo = rec.receiptNo;
                    if (rec.receiptNo.includes('/P')) {
                        baseNo = rec.receiptNo.split('/P')[0];
                    }
                    
                    if (!baseReceiptGroups[baseNo]) {
                        baseReceiptGroups[baseNo] = {
                            baseReceiptNo: baseNo,
                            lastReceiptNo: rec.receiptNo,
                            totalPayable: 0,
                            totalPaid: 0,
                            billToName: rec.billToName,
                            gstNumber: rec.gstNumber || '',
                            email: rec.email || '',
                            address: rec.address || ''
                        };
                    }
                    
                    const recItems = JSON.parse(rec.items || '[]');
                    if (!rec.receiptNo.includes('/P') || rec.receiptNo.endsWith('/P1')) {
                        recItems.forEach(item => {
                            baseReceiptGroups[baseNo].totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                        });
                    }
                    recItems.forEach(item => {
                        baseReceiptGroups[baseNo].totalPaid += parseFloat(item.paidAmt || item.amount || 0);
                    });
                    
                    baseReceiptGroups[baseNo].lastReceiptNo = rec.receiptNo;
                });
                
                // Find all groups with active balance due
                const activeDues = [];
                Object.keys(baseReceiptGroups).forEach(baseNo => {
                    const group = baseReceiptGroups[baseNo];
                    const due = group.totalPayable - group.totalPaid;
                    if (due > 0.01) {
                        activeDues.push({
                            baseReceiptNo: baseNo,
                            lastReceiptNo: group.lastReceiptNo,
                            dueAmount: due
                        });
                    }
                });
                
                let html = `
                    <div style="background: #e0f2fe; border: 1px solid #0284c7; padding: 12px; border-radius: 6px; margin-top: 10px; font-size: 13px; color: #0369a1; text-align: left; line-height: 1.5;">
                        <strong>Existing Customer Found!</strong><br>
                        Name: ${lastReceipt.billToName}<br>
                        ${lastReceipt.gstNumber ? 'GST: ' + lastReceipt.gstNumber + '<br>' : ''}
                        Previous Receipts: ${existingReceipts.length}<br>`;
                
                if (activeDues.length > 0) {
                    html += `<div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #0284c7;">`;
                    html += `<strong style="color: #0369a1;">Select an Outstanding Due to Continue Payment:</strong><br>`;
                    activeDues.forEach(dueItem => {
                        html += `
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px; background: #ffffff; padding: 6px 8px; border-radius: 4px; border: 1px solid #bae6fd; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <div>
                                    <span style="font-weight: 600; color: #1e293b;">Receipt: ${dueItem.baseReceiptNo}</span><br>
                                    <span style="font-size: 11px; color: #ef4444; font-weight: 500;">Due: ₹${dueItem.dueAmount.toFixed(2)}</span>
                                </div>
                                <button type="button" class="btn-primary" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;" onclick="continuePartPaymentReceipt('${phone}', '${type}', '${dueItem.lastReceiptNo}')">Pay Due</button>
                            </div>
                        `;
                    });
                    html += `</div>`;
                    
                    html += `
                        <div style="margin-top: 12px; display: flex; gap: 8px;">
                            <button type="button" class="btn-primary" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 12px;" onclick="loadExistingCustomerReceipt('${phone}', '${type}')">Create Fresh Receipt</button>
                        </div>
                    `;
                } else {
                    html += `
                        <div style="margin-top: 10px;">
                            <button type="button" class="btn-primary" style="background: #0ea5e9; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 12px;" onclick="loadExistingCustomerReceipt('${phone}', '${type}')">Load Customer Data</button>
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
            const container = document.getElementById(type === 'gst' ? 'existingUserGstReceipt' : 'existingUserNonGstReceipt');
            if (container) container.innerHTML = '';
        });
}

// Load existing customer data for Receipts
function loadExistingCustomerReceipt(phone, type) {
    fetch(`api/get_customer_receipts.php?phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(existingReceipts => {
            if (!existingReceipts || existingReceipts.length === 0) return;
            
            const lastReceipt = existingReceipts[existingReceipts.length - 1];
            
            if (type === 'gst') {
                document.getElementById('gstReceiptBillToName').value = lastReceipt.billToName;
                document.getElementById('gstReceiptModalNumber').value = lastReceipt.gstNumber || '';
                document.getElementById('gstReceiptEmail').value = lastReceipt.email || '';
                
                const form = document.getElementById('gstReceiptForm');
                delete form.dataset.continueFrom;
                delete form.dataset.originalTotalPayable;
                delete form.dataset.cumulativeTotalPaid;
                
                const container = document.getElementById('gstReceiptItemsContainer');
                container.innerHTML = `
                    <div class="invoice-item-row-gst" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
                        <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstReceiptItemAmountChange()">
                        <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                            <input type="checkbox" class="gst-desc-check"> Desc.%
                        </label>
                        <button type="button" class="btn-add-item" onclick="addGstReceiptItem()">+</button>
                    </div>
                `;
                document.getElementById('gstReceiptPaymentSummarySection').style.display = 'none';
                document.getElementById('btnGstReceiptGenerate').style.display = 'none';
            } else {
                document.getElementById('nonGstReceiptBillToName').value = lastReceipt.billToName;
                document.getElementById('nonGstReceiptEmail').value = lastReceipt.email || '';
                document.getElementById('nonGstReceiptAddress').value = lastReceipt.address || '';
                
                const form = document.getElementById('nonGstReceiptForm');
                delete form.dataset.continueFrom;
                delete form.dataset.originalTotalPayable;
                delete form.dataset.cumulativeTotalPaid;
                
                const container = document.getElementById('nonGstReceiptItemsContainer');
                container.innerHTML = `
                    <div class="invoice-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
                        <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstReceiptItemAmountChange()">
                        <button type="button" class="btn-add-item" onclick="addNonGstReceiptItem()">+</button>
                    </div>
                `;
                document.getElementById('nonGstReceiptPaymentSummarySection').style.display = 'none';
                document.getElementById('btnNonGstReceiptGenerate').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error loading customer:', error);
        });
}

// Continue part payment for Receipts
function continuePartPaymentReceipt(phone, type, targetLastReceiptNo) {
    fetch(`api/get_customer_receipts.php?phone=${encodeURIComponent(phone)}&type=${type}`)
        .then(response => response.json())
        .then(existingReceipts => {
            if (existingReceipts.length === 0) return;
            
            // Extract the base receipt of targetLastReceiptNo
            let targetBaseNo = targetLastReceiptNo;
            if (targetLastReceiptNo.includes('/P')) {
                targetBaseNo = targetLastReceiptNo.split('/P')[0];
            }
            
            let totalPayable = 0;
            let totalPaid = 0;
            let lastReceiptRecord = null;
            
            existingReceipts.forEach(rec => {
                let recBase = rec.receiptNo;
                if (rec.receiptNo.includes('/P')) {
                    recBase = rec.receiptNo.split('/P')[0];
                }
                
                if (recBase === targetBaseNo) {
                    lastReceiptRecord = rec;
                    const items = JSON.parse(rec.items || '[]');
                    
                    if (!rec.receiptNo.includes('/P') || rec.receiptNo.endsWith('/P1')) {
                        items.forEach(item => {
                            totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                        });
                    }
                    
                    items.forEach(item => {
                        totalPaid += parseFloat(item.paidAmt || item.amount || 0);
                    });
                }
            });
            
            if (!lastReceiptRecord) return;
            
            const balanceDue = totalPayable - totalPaid;
            
            if (type === 'gst') {
                document.getElementById('gstReceiptBillToName').value = lastReceiptRecord.billToName;
                document.getElementById('gstReceiptModalNumber').value = lastReceiptRecord.gstNumber || '';
                document.getElementById('gstReceiptEmail').value = lastReceiptRecord.email || '';
                document.getElementById('gstReceiptAddress').value = lastReceiptRecord.address || '';
                
                const container = document.getElementById('gstReceiptItemsContainer');
                container.innerHTML = '';
                
                const today = new Date().toISOString().split('T')[0];
                const newRow = document.createElement('div');
                newRow.className = 'invoice-item-row-gst';
                newRow.style = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
                newRow.innerHTML = `
                    <input type="text" class="form-input gst-desc" placeholder="Payment Description" value="Part Payment" required style="flex: 3;">
                    <input type="number" class="form-input gst-total-incl" placeholder="Balance Due" value="${balanceDue.toFixed(2)}" step="0.01" min="0" readonly style="flex: 1; background: #f1f5f9;" oninput="onGstReceiptItemAmountChange()">
                    <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                        <input type="checkbox" class="gst-desc-check"> Desc.%
                    </label>
                    <button type="button" class="btn-add-item" onclick="addGstReceiptItem()">+</button>
                `;
                container.appendChild(newRow);
                
                document.getElementById('gstReceiptPaymentDate').value = today;
                document.getElementById('gstReceiptAmountPaid').value = balanceDue.toFixed(2);
                
                const form = document.getElementById('gstReceiptForm');
                form.dataset.continueFrom = targetLastReceiptNo;
                form.dataset.originalTotalPayable = totalPayable.toFixed(2);
                form.dataset.cumulativeTotalPaid = totalPaid.toFixed(2);
                
                document.getElementById('gstReceiptPaymentSummarySection').style.display = 'block';
                document.getElementById('btnGstReceiptGenerate').style.display = 'inline-block';
                
                updateGstReceiptSummary();
            } else {
                document.getElementById('nonGstReceiptBillToName').value = lastReceiptRecord.billToName;
                document.getElementById('nonGstReceiptEmail').value = lastReceiptRecord.email || '';
                document.getElementById('nonGstReceiptAddress').value = lastReceiptRecord.address || '';
                
                const container = document.getElementById('nonGstReceiptItemsContainer');
                container.innerHTML = '';
                
                const today = new Date().toISOString().split('T')[0];
                const newRow = document.createElement('div');
                newRow.className = 'invoice-item-row';
                newRow.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
                newRow.innerHTML = `
                    <input type="text" class="form-input nongst-item-desc" placeholder="Description" value="Part Payment" required style="flex: 3;">
                    <input type="number" class="form-input nongst-item-amount" placeholder="Amount" value="${balanceDue.toFixed(2)}" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstReceiptItemAmountChange()">
                    <button type="button" class="btn-add-item" onclick="addNonGstReceiptItem()">+</button>
                `;
                container.appendChild(newRow);
                
                document.getElementById('nonGstReceiptPaymentDate').value = today;
                document.getElementById('nonGstReceiptAmountPaid').value = balanceDue.toFixed(2);
                
                const form = document.getElementById('nonGstReceiptForm');
                form.dataset.continueFrom = targetLastReceiptNo;
                form.dataset.originalTotalPayable = totalPayable.toFixed(2);
                form.dataset.cumulativeTotalPaid = totalPaid.toFixed(2);
                
                document.getElementById('nonGstReceiptPaymentSummarySection').style.display = 'block';
                document.getElementById('btnNonGstReceiptGenerate').style.display = 'inline-block';
                
                updateNonGstReceiptSummary();
            }
        })
        .catch(error => {
            console.error('Error continuing part payment:', error);
            alert('Failed to load customer data. Please try again.');
        });
}

// Modal Helpers
function showReceiptTypeSelection() {
    const div = document.getElementById('receiptTypeSelectionDiv');
    div.style.display = div.style.display === 'none' ? 'grid' : 'none';
}

function selectReceiptType(type) {
    document.getElementById('receiptTypeSelectionDiv').style.display = 'none';
    if (type === 'non-gst') {
        document.getElementById('nonGstReceiptModal').classList.add('show');
    } else if (type === 'gst') {
        document.getElementById('gstReceiptModal').classList.add('show');
    }
}

function closeGstReceiptModal() {
    document.getElementById('gstReceiptModal').classList.remove('show');
    document.getElementById('gstReceiptForm').reset();
    
    const form = document.getElementById('gstReceiptForm');
    delete form.dataset.editReceiptNo;
    delete form.dataset.continueFrom;
    delete form.dataset.originalTotalPayable;
    delete form.dataset.cumulativeTotalPaid;
    
    document.getElementById('gstReceiptPaymentSummarySection').style.display = 'none';
    document.getElementById('btnGstReceiptGenerate').style.display = 'none';
    
    const container = document.getElementById('gstReceiptItemsContainer');
    container.innerHTML = `
        <div class="invoice-item-row-gst" style="display: flex; gap: 8px; margin-bottom: 8px;">
            <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
            <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstReceiptItemAmountChange()">
            <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
                <input type="checkbox" class="gst-desc-check"> Desc.%
            </label>
            <button type="button" class="btn-add-item" onclick="addGstReceiptItem()">+</button>
        </div>
    `;
    
    const lookupContainer = document.getElementById('existingUserGstReceipt');
    if (lookupContainer) lookupContainer.innerHTML = '';
}

function closeNonGstReceiptModal() {
    document.getElementById('nonGstReceiptModal').classList.remove('show');
    document.getElementById('nonGstReceiptForm').reset();
    
    const form = document.getElementById('nonGstReceiptForm');
    delete form.dataset.editReceiptNo;
    delete form.dataset.continueFrom;
    delete form.dataset.originalTotalPayable;
    delete form.dataset.cumulativeTotalPaid;
    
    document.getElementById('nonGstReceiptPaymentSummarySection').style.display = 'none';
    document.getElementById('btnNonGstReceiptGenerate').style.display = 'none';
    
    const container = document.getElementById('nonGstReceiptItemsContainer');
    container.innerHTML = `
        <div class="invoice-item-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
            <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
            <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstReceiptItemAmountChange()">
            <button type="button" class="btn-add-item" onclick="addNonGstReceiptItem()">+</button>
        </div>
    `;
    
    const lookupContainer = document.getElementById('existingUserNonGstReceipt');
    if (lookupContainer) lookupContainer.innerHTML = '';
}

// Item row management
function addGstReceiptItem() {
    const rows = document.querySelectorAll('#gstReceiptItemsContainer .invoice-item-row-gst');
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

    const container = document.getElementById('gstReceiptItemsContainer');
    const row = document.createElement('div');
    row.className = 'invoice-item-row-gst';
    row.style = 'display: flex; gap: 8px; margin-bottom: 8px; align-items: center;';
    row.innerHTML = `
        <input type="text" class="form-input gst-desc" placeholder="Description" required style="flex: 3;">
        <input type="number" class="form-input gst-total-incl" placeholder="Charges (incl tax)" step="0.01" min="0" required style="flex: 1;" oninput="onGstReceiptItemAmountChange()">
        <label style="display: flex; align-items: center; gap: 5px; flex-shrink: 0; padding: 0 5px;">
            <input type="checkbox" class="gst-desc-check"> Desc.%
        </label>
        <button type="button" class="btn-remove-item" onclick="removeGstReceiptItem(this)">−</button>
    `;
    container.appendChild(row);
}

function addNonGstReceiptItem() {
    const rows = document.querySelectorAll('#nonGstReceiptItemsContainer .invoice-item-row');
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

    const container = document.getElementById('nonGstReceiptItemsContainer');
    const row = document.createElement('div');
    row.className = 'invoice-item-row';
    row.style = 'display: flex; gap: 8px; margin-bottom: 8px;';
    row.innerHTML = `
        <input type="text" class="form-input nongst-item-desc" placeholder="Description" required style="flex: 3;">
        <input type="number" class="form-input nongst-item-amount" placeholder="Amount" step="0.01" min="0" required style="flex: 1;" oninput="onNonGstReceiptItemAmountChange()">
        <button type="button" class="btn-remove-item" onclick="removeNonGstReceiptItem(this)">−</button>
    `;
    container.appendChild(row);
    onNonGstReceiptItemAmountChange();
}

function removeNonGstReceiptItem(button) {
    button.parentElement.remove();
    onNonGstReceiptItemAmountChange();
}

function removeGstReceiptItem(button) {
    button.parentElement.remove();
    onGstReceiptItemAmountChange();
}

function onNonGstReceiptItemAmountChange() {
    const section = document.getElementById('nonGstReceiptPaymentSummarySection');
    if (section && section.style.display !== 'none') {
        updateNonGstReceiptSummary();
    }
}

function onGstReceiptItemAmountChange() {
    const section = document.getElementById('gstReceiptPaymentSummarySection');
    if (section && section.style.display !== 'none') {
        updateGstReceiptSummary();
    }
}

// Done Action for GST Receipt
function clickGstReceiptDone() {
    const billToName = document.getElementById('gstReceiptBillToName');
    const phone = document.getElementById('gstReceiptPhone');
    const gstNumber = document.getElementById('gstReceiptModalNumber');
    
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
    
    const rows = document.querySelectorAll('#gstReceiptItemsContainer .invoice-item-row-gst');
    if (rows.length === 0) {
        alert('Please add at least one item.');
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
    
    const dateInput = document.getElementById('gstReceiptPaymentDate');
    if (!dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.gst-total-incl');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const amountPaidInput = document.getElementById('gstReceiptAmountPaid');
    if (!amountPaidInput.value) {
        amountPaidInput.value = totalItemsAmt.toFixed(2);
    }
    
    document.getElementById('gstReceiptPaymentSummarySection').style.display = 'block';
    document.getElementById('btnGstReceiptGenerate').style.display = 'inline-block';
    
    updateGstReceiptSummary();
    
    document.getElementById('gstReceiptPaymentSummarySection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Done Action for Non-GST Receipt
function clickNonGstReceiptDone() {
    const billToName = document.getElementById('nonGstReceiptBillToName');
    const phone = document.getElementById('nonGstReceiptPhone');
    
    if (!billToName.value.trim()) {
        billToName.reportValidity();
        return;
    }
    if (!phone.value.trim()) {
        phone.reportValidity();
        return;
    }
    
    const rows = document.querySelectorAll('#nonGstReceiptItemsContainer .invoice-item-row');
    if (rows.length === 0) {
        alert('Please add at least one item.');
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
    
    const dateInput = document.getElementById('nonGstReceiptPaymentDate');
    if (!dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.nongst-item-amount');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const amountPaidInput = document.getElementById('nonGstReceiptAmountPaid');
    if (!amountPaidInput.value) {
        amountPaidInput.value = totalItemsAmt.toFixed(2);
    }
    
    document.getElementById('nonGstReceiptPaymentSummarySection').style.display = 'block';
    document.getElementById('btnNonGstReceiptGenerate').style.display = 'inline-block';
    
    updateNonGstReceiptSummary();
    
    document.getElementById('nonGstReceiptPaymentSummarySection').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function updateNonGstReceiptSummary() {
    const rows = document.querySelectorAll('#nonGstReceiptItemsContainer .invoice-item-row');
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.nongst-item-amount');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const form = document.getElementById('nonGstReceiptForm');
    
    let totalPayable = totalItemsAmt;
    let prevPaid = 0;
    
    if (form.dataset.continueFrom) {
        totalPayable = parseFloat(form.dataset.originalTotalPayable) || totalItemsAmt;
        prevPaid = parseFloat(form.dataset.cumulativeTotalPaid) || 0;
    }
    
    const amountPaid = parseFloat(document.getElementById('nonGstReceiptAmountPaid').value) || 0;
    const totalPaidSoFar = prevPaid + amountPaid;
    const balanceDue = Math.max(0, totalPayable - totalPaidSoFar);
    
    document.getElementById('summaryNonGstReceiptTotal').innerText = '₹' + totalPayable.toFixed(2);
    document.getElementById('summaryNonGstReceiptPaid').innerText = '₹' + amountPaid.toFixed(2);
    
    const dueElement = document.getElementById('summaryNonGstReceiptDue');
    dueElement.innerText = '₹' + balanceDue.toFixed(2);
    if (balanceDue > 0.01) {
        dueElement.style.color = '#dc2626';
    } else {
        dueElement.style.color = '#10b981';
    }
}

function updateGstReceiptSummary() {
    const rows = document.querySelectorAll('#gstReceiptItemsContainer .invoice-item-row-gst');
    let totalItemsAmt = 0;
    rows.forEach(row => {
        const amtInput = row.querySelector('.gst-total-incl');
        totalItemsAmt += parseFloat(amtInput.value) || 0;
    });
    
    const form = document.getElementById('gstReceiptForm');
    
    let totalPayable = totalItemsAmt;
    let prevPaid = 0;
    
    if (form.dataset.continueFrom) {
        totalPayable = parseFloat(form.dataset.originalTotalPayable) || totalItemsAmt;
        prevPaid = parseFloat(form.dataset.cumulativeTotalPaid) || 0;
    }
    
    const exclTax = totalPayable / 1.18;
    const gstAmt = totalPayable - exclTax;
    
    const amountPaid = parseFloat(document.getElementById('gstReceiptAmountPaid').value) || 0;
    const totalPaidSoFar = prevPaid + amountPaid;
    const balanceDue = Math.max(0, totalPayable - totalPaidSoFar);
    
    document.getElementById('summaryGstReceiptCharges').innerText = '₹' + exclTax.toFixed(2);
    document.getElementById('summaryGstReceiptTax').innerText = '₹' + gstAmt.toFixed(2);
    document.getElementById('summaryGstReceiptTotal').innerText = '₹' + totalPayable.toFixed(2);
    document.getElementById('summaryGstReceiptPaid').innerText = '₹' + amountPaid.toFixed(2);
    
    const dueElement = document.getElementById('summaryGstReceiptDue');
    dueElement.innerText = '₹' + balanceDue.toFixed(2);
    if (balanceDue > 0.01) {
        dueElement.style.color = '#dc2626';
    } else {
        dueElement.style.color = '#10b981';
    }
}

// Save logic
async function generateGstReceipt(event) {
    if (event) event.preventDefault();
    const receiptNo = document.getElementById('gstReceiptForm').dataset.editReceiptNo;
    
    const paymentMode = document.getElementById('gstReceiptPaymentMode').value;
    const paymentDate = document.getElementById('gstReceiptPaymentDate').value;
    const amountPaid = parseFloat(document.getElementById('gstReceiptAmountPaid').value) || 0;
    
    if (!paymentMode) {
        alert('Please select a payment mode.');
        return;
    }
    
    // Calculate total sum of items
    let totalItemsAmt = 0;
    document.querySelectorAll('#gstReceiptItemsContainer .invoice-item-row-gst').forEach(row => {
        const amtVal = parseFloat(row.querySelector('.gst-total-incl').value) || 0;
        totalItemsAmt += amtVal;
    });
    
    const ratio = totalItemsAmt > 0 ? (amountPaid / totalItemsAmt) : 0;
    
    const items = [];
    const rows = document.querySelectorAll('#gstReceiptItemsContainer .invoice-item-row-gst');
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

    const form = document.getElementById('gstReceiptForm');
    const formData = {
        type: 'gst',
        billToName: document.getElementById('gstReceiptBillToName').value,
        phone: document.getElementById('gstReceiptPhone').value,
        gstNumber: document.getElementById('gstReceiptModalNumber').value,
        email: document.getElementById('gstReceiptEmail').value,
        address: document.getElementById('gstReceiptAddress').value || '',
        items: JSON.stringify(items),
        receiptNo: receiptNo || null
    };

    const firstItemDate = items.length > 0 ? items[0].date : null;
    if (firstItemDate) formData.date = firstItemDate;

    if (form.dataset.continueFrom) formData.continueFrom = form.dataset.continueFrom;
    if (form.dataset.originalTotalPayable) formData.originalTotalPayable = form.dataset.originalTotalPayable;
    if (form.dataset.cumulativeTotalPaid) formData.cumulativeTotalPaid = form.dataset.cumulativeTotalPaid;

    saveReceipt(formData);
    closeGstReceiptModal();
}

async function generateNonGstReceipt(event) {
    if (event) event.preventDefault();
    const form = document.getElementById('nonGstReceiptForm');
    const receiptNo = form.dataset.editReceiptNo;
    
    const paymentMode = document.getElementById('nonGstReceiptPaymentMode').value;
    const paymentDate = document.getElementById('nonGstReceiptPaymentDate').value;
    const totalPaid = parseFloat(document.getElementById('nonGstReceiptAmountPaid').value) || 0;
    
    if (!paymentMode) {
        alert('Please select a Payment Mode');
        return;
    }
    if (!paymentDate) {
        alert('Please select a Payment Date');
        return;
    }
    
    const items = [];
    const rows = document.querySelectorAll('#nonGstReceiptItemsContainer .invoice-item-row');
    
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
        billToName: document.getElementById('nonGstReceiptBillToName').value,
        phone: document.getElementById('nonGstReceiptPhone').value,
        email: document.getElementById('nonGstReceiptEmail').value,
        address: document.getElementById('nonGstReceiptAddress').value || '',
        items: JSON.stringify(items),
        receiptNo: receiptNo || null,
        date: paymentDate
    };
    
    if (form.dataset.continueFrom) formData.continueFrom = form.dataset.continueFrom;
    if (form.dataset.originalTotalPayable) formData.originalTotalPayable = form.dataset.originalTotalPayable;
    if (form.dataset.cumulativeTotalPaid) formData.cumulativeTotalPaid = form.dataset.cumulativeTotalPaid;

    saveReceipt(formData);
    closeNonGstReceiptModal();
}

function saveReceipt(receiptData) {
    fetch('api/save_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(receiptData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadReceiptsFromDB();
            if (typeof loadCustomersFromDB === 'function') loadCustomersFromDB();
            viewReceiptByNo(data.receiptNo);
        } else {
            alert('Error: ' + data.error);
        }
    });
}

// Load receipts
function loadReceiptsFromDB() {
    Promise.all([
        fetch('api/get_receipts.php').then(r => r.json()),
        fetch('api/get_invoices.php').then(r => r.json())
    ])
    .then(([recData, invData]) => {
        generatedReceipts = Array.isArray(recData) ? recData : [];
        generatedInvoices = Array.isArray(invData) ? invData : [];
        displayReceipts();
    })
    .catch(err => {
        console.error('Error loading receipts and invoices:', err);
        generatedReceipts = [];
        generatedInvoices = [];
        displayReceipts();
    });
}

// Bulk Actions for Receipts
function triggerReceiptCSVImport() {
    document.getElementById('csv-receipt-file-input').click();
}

async function importReceiptsCSV(event) {
    const file = event.target.files[0];
    if (!file) return;

    const btn = document.querySelector('button[onclick="triggerReceiptCSVImport()"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = '🕒 Processing...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('csvFile', file);

    fetch('api/bulk_receipt_process.php', { method: 'POST', body: formData })
    .then(r => {
        if (!r.ok) throw new Error('Server error');
        return r.json();
    })
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        event.target.value = ''; // Reset file input

        if (data.success) {
            alert('Successfully generated ' + data.count + ' receipts!');
            loadReceiptsFromDB();
            if (typeof loadCustomersFromDB === 'function') loadCustomersFromDB();
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

async function clearAllReceipts() {
    if (confirm('Delete ALL payment receipts data? This cannot be undone.')) {
        fetch('api/clear_all_receipts.php', { method: 'POST' }).then(() => {
            loadReceiptsFromDB();
            if (typeof loadCustomersFromDB === 'function') loadCustomersFromDB();
        });
    }
}

// Search Receipts
function searchReceipts() {
    const query = document.getElementById('receipt-search').value.toLowerCase();
    const rows = document.querySelectorAll('#receipt-list tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Delete receipt by number
function deleteReceiptByNo(receiptNo) {
    if (!confirm(`Are you sure you want to delete receipt ${receiptNo}? This cannot be undone.`)) return;

    fetch('api/delete_receipt.php?receiptNo=' + encodeURIComponent(receiptNo))
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                loadReceiptsFromDB();
                if (typeof loadDashboardData === 'function') loadDashboardData(); 
            } else {
                alert('Error: ' + data.error);
            }
        });
}

// Download individual receipt PDF
function downloadReceiptByNo(receiptNo) {
    window.open('api/download_receipt_pdf.php?receiptNo=' + encodeURIComponent(receiptNo), '_blank');
}

// Variable to hold selected invoice details for payment
var selectedInvoiceForReceipt = null;

function openGenerateReceiptFromInvoiceModal() {
    const modal = document.getElementById('receiptFromInvoiceModal');
    if (!modal) return;
    
    // Reset to Screen 1
    backToInvoiceSelection();
    
    // Clear dynamic body
    const tbody = document.getElementById('dueInvoicesTableBody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">Loading invoices...</td></tr>';
    
    modal.classList.add('show');
    
    // Fetch invoices
    fetch('api/get_invoices.php')
        .then(res => res.json())
        .then(invoices => {
            if (invoices.error) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #ef4444;">Error: ${invoices.error}</td></tr>`;
                return;
            }
            
            // Filter invoices that have balance due > 0.01
            const dueInvoices = invoices.filter(inv => {
                const total = parseFloat(inv.originalTotalPayable || 0);
                const paid = parseFloat(inv.cumulativeTotalPaid || 0);
                return (total - paid) > 0.01;
            });
            
            if (dueInvoices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">No invoices with outstanding balances found.</td></tr>';
                return;
            }
            
            tbody.innerHTML = '';
            dueInvoices.forEach(inv => {
                const total = parseFloat(inv.originalTotalPayable || 0);
                const paid = parseFloat(inv.cumulativeTotalPaid || 0);
                const balance = total - paid;
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);"><strong>${inv.invoiceNo}</strong></td>
                    <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">${inv.billToName}</td>
                    <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);">₹${total.toFixed(2)}</td>
                    <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #10b981;">₹${paid.toFixed(2)}</td>
                    <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ef4444; font-weight: 600;">₹${balance.toFixed(2)}</td>
                    <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: center;">
                        <button class="btn-action" onclick='selectInvoiceForPayment(${JSON.stringify(inv)})' style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600;">
                            Pay Now
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error fetching invoices:', err);
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px; color: #ef4444;">Failed to load invoices.</td></tr>';
        });
}

function closeReceiptFromInvoiceModal() {
    document.getElementById('receiptFromInvoiceModal').classList.remove('show');
    selectedInvoiceForReceipt = null;
}

function selectInvoiceForPayment(invoice) {
    selectedInvoiceForReceipt = invoice;
    const total = parseFloat(invoice.originalTotalPayable || 0);
    const paid = parseFloat(invoice.cumulativeTotalPaid || 0);
    const balance = total - paid;
    
    // Populate details screen
    document.getElementById('recInvoiceNo').innerText = invoice.invoiceNo;
    document.getElementById('recCustomerName').innerText = invoice.billToName;
    document.getElementById('recTotalAmount').innerText = `₹${total.toFixed(2)}`;
    document.getElementById('recTotalPaid').innerText = `₹${paid.toFixed(2)}`;
    document.getElementById('recBalanceDue').innerText = `₹${balance.toFixed(2)}`;
    
    // Set default payment date to today
    document.getElementById('recPaymentDate').value = new Date().toISOString().split('T')[0];
    // Default current paid to outstanding balance
    document.getElementById('recPaidNow').value = balance.toFixed(2);
    document.getElementById('recPaidNow').max = balance.toFixed(2);
    
    // Switch screen
    document.getElementById('receiptSelectInvoiceScreen').style.display = 'none';
    document.getElementById('receiptRecordPaymentScreen').style.display = 'block';
}

function backToInvoiceSelection() {
    document.getElementById('receiptSelectInvoiceScreen').style.display = 'block';
    document.getElementById('receiptRecordPaymentScreen').style.display = 'none';
    selectedInvoiceForReceipt = null;
}

function submitReceiptPayment(event) {
    event.preventDefault();
    if (!selectedInvoiceForReceipt) return;
    
    const paidNow = parseFloat(document.getElementById('recPaidNow').value || 0);
    if (paidNow <= 0) {
        alert('Please enter a valid payment amount.');
        return;
    }
    
    const maxPayable = parseFloat(selectedInvoiceForReceipt.originalTotalPayable || 0) - parseFloat(selectedInvoiceForReceipt.cumulativeTotalPaid || 0);
    if (paidNow > maxPayable + 0.01) {
        alert(`Payment cannot exceed remaining balance due of ₹${maxPayable.toFixed(2)}`);
        return;
    }
    
    const payload = {
        invoice_no: selectedInvoiceForReceipt.invoiceNo,
        items: JSON.stringify([{
            description: `Payment for Invoice #${selectedInvoiceForReceipt.invoiceNo}`,
            amount: paidNow,
            paidAmt: paidNow,
            paymentMode: document.getElementById('recPaymentMode').value,
            date: document.getElementById('recPaymentDate').value
        }]),
        date: document.getElementById('recPaymentDate').value
    };
    
    fetch('api/save_receipt.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            closeReceiptFromInvoiceModal();
            // Refresh receipts list and statistics
            loadReceiptsFromDB();
            if (typeof loadDashboardData === 'function') loadDashboardData();
            alert('Receipt generated successfully!');
        } else {
            alert('Error generating receipt: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Error saving receipt payment:', err);
        alert('Failed to save receipt payment.');
    });
}
