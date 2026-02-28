// Invoice Management System
let generatedInvoices = [];
let userDatabase = [];

// Initialize: Load from database
document.addEventListener('DOMContentLoaded', function() {
    loadInvoicesFromDB();
    loadCustomersFromDB();
});

// Toggle Invoice Dropdown
function toggleInvoiceDropdown(event) {
    event.preventDefault();
    const dropdown = document.getElementById('invoiceDropdown');
    dropdown.classList.toggle('show');
    event.currentTarget.classList.toggle('active');
}

// Handle dropdown item clicks
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dropdown-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active from all nav items and dropdown items
            document.querySelectorAll('.nav-item, .dropdown-item').forEach(nav => nav.classList.remove('active'));
            
            // Add active to clicked item
            this.classList.add('active');
            
            // Get page name
            const page = this.getAttribute('data-page');
            
            // Hide all pages
            document.querySelectorAll('.page-content').forEach(content => content.classList.add('hidden'));
            
            // Show selected page
            document.getElementById(page + '-page').classList.remove('hidden');
            
            // Update page title
            const titles = {
                'invoice-generate': 'Generate Invoice',
                'invoice-users': 'User Information'
            };
            document.getElementById('page-title').textContent = titles[page] || 'Invoice';
            
            // Load appropriate data
            if (page === 'invoice-generate') {
                loadInvoices();
            } else if (page === 'invoice-users') {
                loadUserInfo();
            }
        });
    });
    
    loadInvoices();
    loadUserInfo();
});

// Check for existing user by phone
function checkExistingUser(phone, type) {
    if (phone.length < 10) return;
    
    // Fetch from database
    fetch(`api/get_customer_invoices.php?phone=${encodeURIComponent(phone)}&type=${type}`)
        .then(response => response.json())
        .then(existingInvoices => {
            const container = document.getElementById(type === 'gst' ? 'existingUserGst' : 'existingUserNonGst');
            
            if (existingInvoices.length > 0) {
                const lastInvoice = existingInvoices[existingInvoices.length - 1];
                
                // Check if last invoice has due amount
                const items = JSON.parse(lastInvoice.items);
                let totalPayable = 0;
                let totalPaid = 0;
                
                // Calculate totals from all invoices for this customer
                existingInvoices.forEach(inv => {
                    const invItems = JSON.parse(inv.items);
                    invItems.forEach(item => {
                        // Only the first invoice (with /P1 or without /P) should count towards totalPayable
                        if (inv.invoiceNo.endsWith('/P1') || !inv.invoiceNo.includes('/P')) {
                            totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                        }
                        totalPaid += parseFloat(item.paidAmt || item.amount || 0);
                    });
                });
                
                const hasDue = totalPayable > totalPaid;
                
                let html = `
                    <div style="background: #e0f2fe; border: 1px solid #0284c7; padding: 10px; border-radius: 6px; margin-top: 10px;">
                        <strong>Existing Customer Found!</strong><br>
                        Name: ${lastInvoice.billToName}<br>
                        ${lastInvoice.gstNumber ? 'GST: ' + lastInvoice.gstNumber + '<br>' : ''}
                        Last Invoice: ${lastInvoice.invoiceNo}<br>
                        Previous Invoices: ${existingInvoices.length}<br>`;
                
                if (hasDue) {
                    const dueAmount = totalPayable - totalPaid;
                    html += `<span style="color: #dc2626;">Balance Due: ₹${dueAmount.toFixed(2)}</span><br>`;
                    html += `<button class="btn-primary" style="margin-top: 10px;" onclick="continuePartPayment('${phone}', '${type}')">Continue Payment</button>`;
                } else {
                    html += `<button class="btn-primary" style="margin-top: 10px;" onclick="loadExistingCustomer('${phone}', '${type}')">Load Customer Data</button>`;
                }
                
                html += `</div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const container = document.getElementById(type === 'gst' ? 'existingUserGst' : 'existingUserNonGst');
            container.innerHTML = '';
        });
}

// Continue part payment
function continuePartPayment(phone, type) {
    // Fetch invoices from database
    fetch(`api/get_customer_invoices.php?phone=${encodeURIComponent(phone)}&type=${type}`)
        .then(response => response.json())
        .then(existingInvoices => {
            if (existingInvoices.length === 0) return;
            
            const lastInvoice = existingInvoices[existingInvoices.length - 1];
            
            // Calculate total payable and total paid from ALL invoices
            let totalPayable = 0;
            let totalPaid = 0;
            
            existingInvoices.forEach(inv => {
                const items = JSON.parse(inv.items);
                items.forEach(item => {
                    // Only the first invoice (with /P1 or without /P) should count towards totalPayable
                    // Subsequent payments (/P2, /P3, etc.) should not add to totalPayable
                    if (inv.invoiceNo.endsWith('/P1') || !inv.invoiceNo.includes('/P')) {
                        totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                    }
                    totalPaid += parseFloat(item.paidAmt || item.amount || 0);
                });
            });
            
            const balanceDue = totalPayable - totalPaid;
            
            // Load customer data
            if (type === 'gst') {
                document.getElementById('gstBillToName').value = lastInvoice.billToName;
                document.getElementById('gstNumber').value = lastInvoice.gstNumber || '';
                document.getElementById('gstEmail').value = lastInvoice.email || '';
                
                // Clear container and add new payment row with balance due pre-filled
                const container = document.getElementById('gstItemsContainer');
                container.innerHTML = '';
                
                const today = new Date().toISOString().split('T')[0];
                const newRow = document.createElement('div');
                newRow.className = 'invoice-item-row-gst';
                newRow.innerHTML = `
                    <input type="text" class="form-input gst-desc" placeholder="Payment Description" value="Part Payment" required style="min-width: 150px;">
                    <select class="form-select gst-mode" required style="min-width: 100px;">
                        <option value="">Payment Mode</option>
                        <option value="Cash">Cash</option>
                        <option value="Online">Online</option>
                    </select>
                    <input type="date" class="form-input gst-date" value="${today}" required style="min-width: 130px;">
                    <input type="number" class="form-input gst-total-incl" placeholder="Balance Due" value="${balanceDue.toFixed(2)}" step="0.01" min="0" readonly style="min-width: 120px; background: #f1f5f9;">
                    <input type="number" class="form-input gst-paid-amt" placeholder="Paying Now" step="0.01" min="0" required style="min-width: 120px;">
                    <input type="number" class="form-input gst-tax" placeholder="GST 18%" step="0.01" readonly style="min-width: 100px; background: #f1f5f9;">
                    <input type="number" class="form-input gst-charges" placeholder="Charges" step="0.01" readonly style="min-width: 100px; background: #f1f5f9;">
                    <label style="display: flex; align-items: center; gap: 5px; min-width: 80px;">
                        <input type="checkbox" class="gst-desc-check"> Desc.%
                    </label>
                    <button type="button" class="btn-add-item" onclick="addGstItem()">+</button>
                `;
                container.appendChild(newRow);
                
                // Mark as continuation and store cumulative totals
                document.getElementById('gstInvoiceForm').dataset.continueFrom = lastInvoice.invoiceNo;
                document.getElementById('gstInvoiceForm').dataset.originalTotalPayable = totalPayable.toFixed(2);
                document.getElementById('gstInvoiceForm').dataset.cumulativeTotalPaid = totalPaid.toFixed(2);
                
            } else {
                // Similar for non-GST
                document.getElementById('nonGstBillToName').value = lastInvoice.billToName;
                document.getElementById('nonGstEmail').value = lastInvoice.email || '';
                
                const container = document.getElementById('nonGstItemsContainer');
                container.innerHTML = '';
                
                const today = new Date().toISOString().split('T')[0];
                const newRow = document.createElement('div');
                newRow.className = 'invoice-item-row';
                newRow.innerHTML = `
                    <input type="text" class="form-input" placeholder="Payment Description" value="Part Payment" required style="flex: 2;">
                    <select class="form-select" required style="flex: 1;">
                        <option value="">Payment Mode</option>
                        <option value="Cash">Cash</option>
                        <option value="Online">Online</option>
                    </select>
                    <input type="date" class="form-input" value="${today}" required style="flex: 1;">
                    <input type="number" class="form-input nongst-total" placeholder="Balance Due" value="${balanceDue.toFixed(2)}" step="0.01" min="0" readonly style="flex: 1; background: #f1f5f9;">
                    <input type="number" class="form-input nongst-paid" placeholder="Paying Now" step="0.01" min="0" required style="flex: 1;">
                    <button type="button" class="btn-add-item" onclick="addNonGstItem()">+</button>
                `;
                container.appendChild(newRow);
                
                document.getElementById('nonGstInvoiceForm').dataset.continueFrom = lastInvoice.invoiceNo;
                document.getElementById('nonGstInvoiceForm').dataset.originalTotalPayable = totalPayable.toFixed(2);
                document.getElementById('nonGstInvoiceForm').dataset.cumulativeTotalPaid = totalPaid.toFixed(2);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load customer data. Please try again.');
        });
}

// Load existing customer data
function loadExistingCustomer(phone, type) {
    fetch(`api/get_customer_invoices.php?phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(existingInvoices => {
            if (existingInvoices.length === 0) return;
            
            const lastInvoice = existingInvoices[existingInvoices.length - 1];
            
            if (type === 'gst') {
                document.getElementById('gstBillToName').value = lastInvoice.billToName;
                document.getElementById('gstNumber').value = lastInvoice.gstNumber || '';
                document.getElementById('gstEmail').value = lastInvoice.email || '';
            } else {
                document.getElementById('nonGstBillToName').value = lastInvoice.billToName;
                document.getElementById('nonGstEmail').value = lastInvoice.email || '';
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Calculate GST from total amount
function calculateGstFromTotal(input) {
    const row = input.parentElement;
    const totalInclTax = parseFloat(row.querySelector('.gst-total-incl').value) || 0;
    
    // Total = Charges + GST
    // Total = Charges * 1.18
    // Charges = Total / 1.18
    
    const charges = totalInclTax / 1.18;
    const gstAmount = totalInclTax - charges;
    
    row.querySelector('.gst-charges').value = charges.toFixed(2);
    row.querySelector('.gst-tax').value = gstAmount.toFixed(2);
}

// Generate sequential invoice number with payment tracking
function generateInvoiceNumber(phone, gstNumber, isContinuation, continueFrom) {
    const year = new Date().getFullYear();
    
    if (isContinuation && continueFrom) {
        // Extract base invoice number and increment payment number
        const match = continueFrom.match(/^(TSK-\d{4}-\d{3})(?:\/P(\d+))?$/);
        if (match) {
            const baseInvoice = match[1];
            const paymentNum = match[2] ? parseInt(match[2]) + 1 : 2;
            return `${baseInvoice}/P${paymentNum}`;
        }
    }
    
    // Check if this customer has existing invoices with due amount
    const customerInvoices = generatedInvoices.filter(inv => 
        inv.phone === phone && (gstNumber ? inv.gstNumber === gstNumber : true)
    );
    
    if (customerInvoices.length > 0) {
        // Find the last invoice with due amount
        const lastInvoiceWithDue = customerInvoices.reverse().find(inv => {
            const items = JSON.parse(inv.items);
            let totalPayable = 0;
            let totalPaid = 0;
            items.forEach(item => {
                totalPayable += parseFloat(item.totalInclTax || item.amount || 0);
                totalPaid += parseFloat(item.paidAmt || item.amount || 0);
            });
            return totalPayable > totalPaid;
        });
        
        if (lastInvoiceWithDue) {
            // This is a part payment, add /P1
            const match = lastInvoiceWithDue.invoiceNo.match(/^(TSK-\d{4}-\d{3})(?:\/P(\d+))?$/);
            if (match) {
                const baseInvoice = match[1];
                return `${baseInvoice}/P1`;
            }
        }
    }
    
    // New invoice
    const baseNumber = generatedInvoices.length + 1;
    return 'TSK-' + year + '-' + String(baseNumber).padStart(3, '0');
}

// Save invoice
function saveInvoice(invoiceData) {
    // Send to database
    fetch('api/save_invoice.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(invoiceData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Save invoice response:', data);
        
        if (data.success) {
            console.log('Invoice saved successfully! Invoice No:', data.invoiceNo);
            
            // Create invoice object for immediate display
            const invoice = {
                invoiceNo: data.invoiceNo,
                ...invoiceData,
                generatedAt: new Date().toISOString()
            };
            
            console.log('Opening invoice:', invoice.invoiceNo);
            
            // Remove temporary fields (but keep null values as they indicate new invoice)
            if (invoice.continueFrom) delete invoice.continueFrom;
            
            // Open invoice window
            openInvoiceWindow(invoice);
            
            // Reload invoices from database (async, doesn't block)
            loadInvoicesFromDB();
        } else {
            alert('Error saving invoice: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save invoice. Please try again.');
    });
}

// Load invoices from database
function loadInvoicesFromDB() {
    fetch('api/get_invoices.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading invoices:', data.error);
                generatedInvoices = [];
            } else {
                generatedInvoices = data;
            }
            displayInvoices();
        })
        .catch(error => {
            console.error('Error:', error);
            generatedInvoices = [];
            displayInvoices();
        });
}

// Load customers from database
function loadCustomersFromDB() {
    fetch('api/get_customers.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error loading customers:', data.error);
                userDatabase = [];
            } else {
                userDatabase = data.map(c => ({
                    name: c.name,
                    phone: c.phone,
                    email: c.email,
                    gstNumber: c.gstNumber,
                    invoices: [], // Will be populated when needed
                    lastInvoiceDate: c.lastInvoiceDate
                }));
            }
            displayUserInfo();
        })
        .catch(error => {
            console.error('Error:', error);
            userDatabase = [];
            displayUserInfo();
        });
}

// Update user database
function updateUserDatabase(invoice) {
    const existingUser = userDatabase.find(u => u.phone === invoice.phone);
    
    if (existingUser) {
        existingUser.invoices.push(invoice.invoiceNo);
        existingUser.lastInvoiceDate = invoice.generatedAt;
    } else {
        userDatabase.push({
            name: invoice.billToName,
            phone: invoice.phone,
            email: invoice.email || 'N/A',
            gstNumber: invoice.gstNumber || 'Not Applicable',
            type: invoice.type,
            invoices: [invoice.invoiceNo],
            lastInvoiceDate: invoice.generatedAt
        });
    }
    
    localStorage.setItem('userDatabase', JSON.stringify(userDatabase));
}

// Load invoices
function loadInvoices() {
    const stored = localStorage.getItem('generatedInvoices');
    if (stored) {
        generatedInvoices = JSON.parse(stored);
        displayInvoices();
    }
}

// Load user info
function loadUserInfo() {
    const stored = localStorage.getItem('userDatabase');
    if (stored) {
        userDatabase = JSON.parse(stored);
        displayUserInfo();
    }
}

// Display invoices
function displayInvoices() {
    const container = document.getElementById('invoice-list');
    
    if (generatedInvoices.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No invoices generated yet. Click "Generate New Invoice" to create one!</p>';
        return;
    }
    
    let html = `<table>
        <thead>
            <tr>
                <th>Invoice No</th>
                <th>Type</th>
                <th>Date Generated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>`;
    
    generatedInvoices.slice().reverse().forEach((invoice, index) => {
        const actualIndex = generatedInvoices.length - 1 - index;
        html += `<tr>
            <td><strong>${invoice.invoiceNo}</strong></td>
            <td><span class="category-badge">${invoice.type === 'gst' ? 'GST' : 'Non-GST'}</span></td>
            <td>${new Date(invoice.generatedAt).toLocaleDateString()}</td>
            <td>
                <button class="btn-action btn-edit" onclick='editInvoice(${actualIndex})' title="Edit">✏️</button>
                <button class="btn-action btn-edit" onclick='viewInvoice(${actualIndex})' title="View">👁️</button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// Display user info
function displayUserInfo() {
    const container = document.getElementById('user-info-list');
    
    if (userDatabase.length === 0) {
        container.innerHTML = '<p style="padding: 40px; text-align: center; color: #64748b;">No users found.</p>';
        return;
    }
    
    let html = `<table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Email</th>
                <th>GST Number</th>
                <th>Last Invoice</th>
            </tr>
        </thead>
        <tbody>`;
    
    userDatabase.forEach(user => {
        html += `<tr>
            <td><strong>${user.name}</strong></td>
            <td>${user.phone}</td>
            <td>${user.email}</td>
            <td>${user.gstNumber}</td>
            <td>${user.lastInvoiceDate ? new Date(user.lastInvoiceDate).toLocaleDateString() : 'N/A'}</td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

// View invoice
function viewInvoice(index) {
    const invoice = generatedInvoices[index];
    openInvoiceWindow(invoice);
}

// Open invoice window
function openInvoiceWindow(invoiceData) {
    // Debug: Log the invoice data
    console.log('Opening invoice with data:', invoiceData);
    console.log('Invoice Number:', invoiceData.invoiceNo);
    
    // If invoice has been saved to database (has invoiceNo), fetch from database
    if (invoiceData.invoiceNo && !invoiceData.invoiceNo.includes('undefined')) {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const url = `api/view_invoice.php?invoiceNo=${encodeURIComponent(invoiceData.invoiceNo)}&t=${timestamp}`;
        console.log('Opening URL:', url);
        // Use unique target name to force new tab
        window.open(url, '_blank');
    } else {
        console.log('Fallback: passing data through URL');
        // Fallback: pass data through URL (for unsaved invoices)
        const params = new URLSearchParams(invoiceData).toString();
        window.open(`api/generate_invoice.php?${params}`, '_blank');
    }
}

// Show invoice type selection
function showInvoiceTypeSelection() {
    const div = document.getElementById('invoiceTypeSelectionDiv');
    div.style.display = div.style.display === 'none' ? 'grid' : 'none';
}

// Select invoice type
function selectInvoiceType(type) {
    document.getElementById('invoiceTypeSelectionDiv').style.display = 'none';
    if (type === 'non-gst') {
        document.getElementById('nonGstInvoiceModal').classList.add('show');
    } else if (type === 'gst') {
        document.getElementById('gstInvoiceModal').classList.add('show');
    }
}
