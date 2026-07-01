// ==========================================
// Quotation Builder Module
// ==========================================

let currentQuotationStep = 1;
const totalQuotationSteps = 3;
let quotationScopeModules = [];
let quotationTerms = [
    { title: '1', content: 'This quotation is valid for 15 days from the date of issue.' },
    { title: '2', content: 'The project will commence upon receipt of 30% advance payment of the total quoted amount.' },
    { title: '3', content: 'The quotation includes only the scope of work mentioned below.' },
    { title: '4', content: 'Any additional features or changes will be charged separately.' },
    { title: '5', content: 'The client is responsible for providing the required content and timely approvals.' },
    { title: '6', content: 'Third-party charges (hosting, domain, APIs, etc.), if applicable, will be borne by the client.' },
    { title: '7', content: 'Project ownership will be transferred after full payment is received.' },
    { title: '8', content: 'Advance payment is non-refundable once the project has commenced.' }
];

// Presets for Quick Add
const PRESET_MODULES = {
    'Landing Page': ['Hero Banner', 'About Us', 'Featured Products', 'Categories', 'Contact Form', 'Testimonials', 'Footer'],
    'Customer Panel': ['Registration & Profile', 'Secure Login', 'Wishlist', 'Shopping Cart', 'Checkout Flow', 'Order History'],
    'Admin Panel': ['Analytics Dashboard', 'Product Management', 'Order Management', 'Customer Management', 'System Reports & Settings'],
    'Vendor Panel': ['Vendor Registration', 'Store Setup', 'Inventory Upload', 'Order Dispatch', 'Payout Analytics'],
    'Delivery Panel': ['Rider Dashboard', 'Order Navigation', 'Status Updates', 'Signature Capture'],
    'Payment Gateway': ['UPI Integration', 'Credit/Debit Card Payments', 'Netbanking Options', 'Automatic Refund Management'],
    'Reports': ['Daily Summary', 'Transactional Statements', 'Tax Invoices', 'User Logs'],
    'Analytics': ['Visitor Analytics', 'Goal Tracking', 'Click Tracking', 'Conversion Analysis'],
    'Mobile App': ['Push Notifications', 'Offline Storage', 'App Store Assets', 'Biometric Auth'],
    'Inventory': ['Stock Tracking', 'Supplier Register', 'Low Stock Alert', 'Barcode Scanning']
};

// 1. Modal Control & Edit Mode Setup
function openQuotationModal(quoteNo = null) {
    const modal = document.getElementById('quotationModal');
    if (!modal) return;
    
    // Reset Form Elements
    document.getElementById('quotationId').value = '';
    document.getElementById('qClientPhone').value = '';
    document.getElementById('qClientName').value = '';
    document.getElementById('qClientEmail').value = '';
    document.getElementById('qClientGst').value = '';
    document.getElementById('qClientAddress').value = '';
    document.getElementById('qDate').value = new Date().toISOString().split('T')[0];
    document.getElementById('qProjectName').value = '';
    document.getElementById('qProjectDescription').value = '';
    
    document.getElementById('qItemsTableBody').innerHTML = '';
    document.getElementById('qDiscountInput').value = '0';
    document.getElementById('qGstRateInput').value = '18';
    
    const clientSignNameEl = document.getElementById('qClientSignName');
    const clientSignDateEl = document.getElementById('qClientSignDate');
    if (clientSignNameEl) clientSignNameEl.value = '';
    if (clientSignDateEl) clientSignDateEl.value = '';
    
    const includeScopeCheckbox = document.getElementById('qIncludeScope');
    if (includeScopeCheckbox) {
        includeScopeCheckbox.checked = true;
    }
    
    quotationScopeModules = [];
    
    document.getElementById('quotationModalTitle').textContent = 'Create Corporate Quotation';
    
    // Check mode
    if (quoteNo && typeof allQuotations !== 'undefined') {
        const q = allQuotations.find(quote => quote.quotationNo === quoteNo);
        if (q) {
            document.getElementById('quotationId').value = quoteNo;
            document.getElementById('quotationModalTitle').textContent = 'Edit Quotation: ' + quoteNo;
            document.getElementById('qClientPhone').value = q.phone || '';
            document.getElementById('qClientName').value = q.clientName || '';
            document.getElementById('qClientEmail').value = q.email || '';
            document.getElementById('qClientGst').value = q.gstNumber || '';
            document.getElementById('qDate').value = q.date || '';
            
            // Client address fetch
            fetch('api/get_clients.php')
                .then(res => res.json())
                .then(clients => {
                    const c = clients.find(client => client.phone === q.phone);
                    if (c && c.address) {
                        document.getElementById('qClientAddress').value = c.address;
                        updateQuotationPreview();
                    }
                });

            // Detect if new structured format is saved
            const isNewFormat = q.items && !Array.isArray(q.items) && q.items.commercial_items;
            
            if (isNewFormat) {
                const data = q.items;
                document.getElementById('qProjectName').value = data.project_name || '';
                document.getElementById('qProjectDescription').value = data.project_description || '';
                document.getElementById('qDiscountInput').value = data.discount || 0;
                document.getElementById('qGstRateInput').value = data.gst_percent !== undefined ? data.gst_percent : 18;
                
                if (includeScopeCheckbox) {
                    includeScopeCheckbox.checked = data.include_scope !== undefined ? data.include_scope : true;
                }
                
                // Populate items
                if (data.commercial_items && Array.isArray(data.commercial_items)) {
                    data.commercial_items.forEach(item => {
                        const actualAmount = item.amount !== undefined ? item.amount : (item.rate * (item.qty || 1));
                        addQuotationItemRowNew(item.description, 1, actualAmount);
                    });
                }
                
                // Populate modules
                if (data.scope_of_work && Array.isArray(data.scope_of_work)) {
                    quotationScopeModules = data.scope_of_work.map((mod, idx) => ({
                        id: 'mod_' + Date.now() + '_' + idx,
                        name: mod.module_name,
                        description: mod.description || '',
                        features: mod.features || []
                    }));
                }
                
                // Populate terms (always use defaults)
                quotationTerms = [
                    { title: '1', content: 'This quotation is valid for 15 days from the date of issue.' },
                    { title: '2', content: 'The project will commence upon receipt of 30% advance payment of the total quoted amount.' },
                    { title: '3', content: 'The quotation includes only the scope of work mentioned below.' },
                    { title: '4', content: 'Any additional features or changes will be charged separately.' },
                    { title: '5', content: 'The client is responsible for providing the required content and timely approvals.' },
                    { title: '6', content: 'Third-party charges (hosting, domain, APIs, etc.), if applicable, will be borne by the client.' },
                    { title: '7', content: 'Project ownership will be transferred after full payment is received.' },
                    { title: '8', content: 'Advance payment is non-refundable once the project has commenced.' }
                ];
                
                // Populate client sign (safe-guarded in case elements are removed)
                const clientSignNameEl = document.getElementById('qClientSignName');
                const clientSignDateEl = document.getElementById('qClientSignDate');
                if (clientSignNameEl) clientSignNameEl.value = data.client_signature_name || '';
                if (clientSignDateEl) clientSignDateEl.value = data.client_signature_date || '';
                
            } else {
                // Legacy flat items format
                document.getElementById('qProjectName').value = 'Project Proposal';
                if (Array.isArray(q.items)) {
                    q.items.forEach(item => {
                        addQuotationItemRowNew(item.description, 1, item.rate * (item.qty || 1));
                    });
                } else {
                    addQuotationItemRowNew('', 1, 0);
                }
            }
        }
    } else {
        // Create fresh
        addQuotationItemRowNew('', 1, 0);
        // Default terms preset
        quotationTerms = [
            { title: '1', content: 'This quotation is valid for 15 days from the date of issue.' },
            { title: '2', content: 'The project will commence upon receipt of 30% advance payment of the total quoted amount.' },
            { title: '3', content: 'The quotation includes only the scope of work mentioned below.' },
            { title: '4', content: 'Any additional features or changes will be charged separately.' },
            { title: '5', content: 'The client is responsible for providing the required content and timely approvals.' },
            { title: '6', content: 'Third-party charges (hosting, domain, APIs, etc.), if applicable, will be borne by the client.' },
            { title: '7', content: 'Project ownership will be transferred after full payment is received.' },
            { title: '8', content: 'Advance payment is non-refundable once the project has commenced.' }
        ];
    }
    
    // Render current lists
    renderScopeModules();
    renderTermsList();
    
    // Switch to step 1
    switchQuotationTab('info');
    
    // Show Modal
    modal.classList.add('show');
    
    // Update live preview
    calculateQuotationSummaryNew();
    
    // Hook drag and drop
    setTimeout(initQuotationDragAndDrop, 200);
}

function closeQuotationModal() {
    const modal = document.getElementById('quotationModal');
    if (modal) modal.classList.remove('show');
}

// 2. Tab & Navigation System
function toggleScopeStepVisibility() {
    const includeScope = document.getElementById('qIncludeScope') ? document.getElementById('qIncludeScope').checked : true;
    const scopeBtn = document.getElementById('tab-btn-scope');
    if (scopeBtn) {
        scopeBtn.style.display = includeScope ? 'inline-block' : 'none';
    }
    
    // If scope panel is active and we just turned it off, switch to commercial
    const scopePanel = document.getElementById('step-scope');
    if (!includeScope && scopePanel && scopePanel.classList.contains('active')) {
        switchQuotationTab('commercial');
    } else {
        // Refresh the current active panel to update the next/prev buttons
        let activeTab = 'info';
        const activePanels = includeScope ? ['info', 'commercial', 'scope'] : ['info', 'commercial'];
        activePanels.forEach(p => {
            const panel = document.getElementById('step-' + p);
            if (panel && panel.classList.contains('active')) {
                activeTab = p;
            }
        });
        switchQuotationTab(activeTab);
    }
}

function switchQuotationTab(tabName) {
    const includeScope = document.getElementById('qIncludeScope') ? document.getElementById('qIncludeScope').checked : true;
    const panels = ['info', 'commercial', 'scope', 'terms'];
    const activePanels = includeScope ? ['info', 'commercial', 'scope'] : ['info', 'commercial'];
    
    // Set visibility of the scope tab button
    const scopeBtn = document.getElementById('tab-btn-scope');
    if (scopeBtn) {
        scopeBtn.style.display = includeScope ? 'inline-block' : 'none';
    }
    
    if (!includeScope && tabName === 'scope') {
        tabName = 'commercial';
    }
    
    panels.forEach(p => {
        const btn = document.getElementById('tab-btn-' + p);
        const panel = document.getElementById('step-' + p);
        if (btn) btn.classList.remove('active');
        if (panel) panel.classList.remove('active');
    });
    
    const activeBtn = document.getElementById('tab-btn-' + tabName);
    const activePanel = document.getElementById('step-' + tabName);
    if (activeBtn) activeBtn.classList.add('active');
    if (activePanel) activePanel.classList.add('active');
    
    // Update navigation buttons
    const idx = activePanels.indexOf(tabName) + 1;
    currentQuotationStep = idx;
    
    document.getElementById('btnQuotationPrev').style.display = idx === 1 ? 'none' : 'inline-block';
    
    if (idx === activePanels.length) {
        document.getElementById('btnQuotationNext').style.display = 'none';
        document.getElementById('btnQuotationSubmit').style.display = 'inline-block';
    } else {
        document.getElementById('btnQuotationNext').style.display = 'inline-block';
        document.getElementById('btnQuotationSubmit').style.display = 'none';
    }
    
    updateQuotationPreview();
}

function navigateQuotationStep(dir) {
    const includeScope = document.getElementById('qIncludeScope') ? document.getElementById('qIncludeScope').checked : true;
    const activePanels = includeScope ? ['info', 'commercial', 'scope'] : ['info', 'commercial'];
    
    let currentActiveTab = 'info';
    activePanels.forEach(p => {
        const panel = document.getElementById('step-' + p);
        if (panel && panel.classList.contains('active')) {
            currentActiveTab = p;
        }
    });
    
    let currentIdx = activePanels.indexOf(currentActiveTab);
    let targetIdx = currentIdx + dir;
    if (targetIdx >= 0 && targetIdx < activePanels.length) {
        switchQuotationTab(activePanels[targetIdx]);
    }
}

// 3. Client Auto Fill
document.getElementById('qClientPhone').addEventListener('input', function(e) {
    const phone = e.target.value;
    if (phone.length >= 10) {
        fetch('api/get_clients.php')
            .then(res => res.json())
            .then(clients => {
                const c = clients.find(client => client.phone === phone);
                if (c) {
                    document.getElementById('qClientName').value = c.name || '';
                    document.getElementById('qClientEmail').value = c.email || '';
                    document.getElementById('qClientGst').value = c.gst_number || '';
                    document.getElementById('qClientAddress').value = c.address || '';
                    updateQuotationPreview();
                }
            });
    }
});

// Sync inputs to live preview instantly
['qClientPhone', 'qClientName', 'qClientEmail', 'qClientGst', 'qClientAddress', 'qProjectName', 'qProjectDescription', 'qDate'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateQuotationPreview);
});

// 4. Commercial Details Items Logic
function addQuotationItemRowNew(desc = '', qty = 1, rate = 0) {
    const tbody = document.getElementById('qItemsTableBody');
    if (!tbody) return;
    
    const tr = document.createElement('tr');
    tr.className = 'sortable-item';
    tr.style.verticalAlign = 'middle';
    tr.innerHTML = `
        <td style="width: 40px; text-align: center; vertical-align: middle;"><span class="drag-handle" draggable="true" style="padding-top: 10px;">☰</span></td>
        <td><input type="text" class="form-input q-item-desc" value="${desc}" placeholder="Item Description" required style="width: 100%; color: var(--text-main); background: #fff;" oninput="calculateQuotationSummaryNew()"></td>
        <td style="display: none;"><input type="number" class="form-input q-item-qty" value="1"></td>
        <td style="width: 150px;"><input type="number" class="form-input q-item-rate" value="${rate}" step="0.01" min="0" required style="width: 100%; text-align: right; color: var(--text-main); background: #fff;" oninput="calculateQuotationSummaryNew()"></td>
        <td style="display: none;" class="q-item-amount-val">₹0.00</td>
        <td style="width: 50px; text-align: center; vertical-align: middle;"><button type="button" class="btn-remove-item" onclick="removeQuotationItemRowNew(this)" style="padding: 6px 10px; cursor: pointer; border-radius: 6px;">&times;</button></td>
    `;
    tbody.appendChild(tr);
    calculateQuotationSummaryNew();
}

function removeQuotationItemRowNew(btn) {
    const row = btn.closest('tr');
    if (row) {
        row.remove();
        calculateQuotationSummaryNew();
    }
}

function calculateQuotationSummaryNew() {
    let totalAmt = 0;
    document.querySelectorAll('#qItemsTableBody tr').forEach(row => {
        const amount = parseFloat(row.querySelector('.q-item-rate').value) || 0;
        totalAmt += amount;
        
        if (row.querySelector('.q-item-qty')) {
            row.querySelector('.q-item-qty').value = 1;
        }
        if (row.querySelector('.q-item-amount-val')) {
            row.querySelector('.q-item-amount-val').textContent = '₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
    });
    
    const discount = parseFloat(document.getElementById('qDiscountInput').value) || 0;
    const grandTotal = Math.max(0, totalAmt - discount);
    
    const gstPercent = parseFloat(document.getElementById('qGstRateInput').value) || 0;
    const subtotal = grandTotal / (1 + (gstPercent / 100));
    const gst = grandTotal - subtotal;
    
    const halfGstPercent = gstPercent / 2;
    const halfGstAmount = gst / 2;
    
    document.getElementById('qSubtotalVal').textContent = '₹' + totalAmt.toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('qDiscountVal').textContent = '-₹' + discount.toLocaleString('en-IN', {minimumFractionDigits: 2});
    
    if (document.getElementById('qCgstLabel')) {
        document.getElementById('qCgstLabel').textContent = `CGST (${halfGstPercent}%):`;
        document.getElementById('qCgstVal').textContent = '₹' + halfGstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
    }
    if (document.getElementById('qSgstLabel')) {
        document.getElementById('qSgstLabel').textContent = `SGST (${halfGstPercent}%):`;
        document.getElementById('qSgstVal').textContent = '₹' + halfGstAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
    }
    
    document.getElementById('qGrandTotalVal').textContent = '₹' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    
    updateQuotationPreview();
}

// 5. Scope Builder Actions
function renderScopeModules() {
    const container = document.getElementById('qScopeModulesList');
    if (!container) return;
    
    if (quotationScopeModules.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 30px; background: #fff; border: 1px dashed #cbd5e1; border-radius: 10px; color: #64748b;">
                <p style="margin-bottom: 12px; font-weight: 500;">No scope modules added yet.</p>
                <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; max-width: 480px; margin: 0 auto;">
                    ${Object.keys(PRESET_MODULES).map(p => `
                        <button type="button" class="btn-secondary" style="padding: 4px 10px; font-size: 11px; margin: 2px;" onclick="addPresetScopeModule('${p}')">${p}</button>
                    `).join('')}
                </div>
            </div>
        `;
        return;
    }
    
    // Quick Add list as small chips above modules
    let html = `
        <div style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <span style="font-size: 12px; font-weight: 700; color: var(--text-muted);">Quick Add Preset:</span>
            ${Object.keys(PRESET_MODULES).map(p => `
                <button type="button" class="btn-secondary" style="padding: 4px 8px; font-size: 11px; margin: 1px;" onclick="addPresetScopeModule('${p}')">+ ${p}</button>
            `).join('')}
        </div>
    `;
    
    quotationScopeModules.forEach((mod, index) => {
        const isCollapsed = mod.isCollapsed ? 'display: none;' : '';
        const arrow = mod.isCollapsed ? '▼' : '▲';
        
        html += `
            <div class="module-card sortable-item" data-id="${mod.id}">
                <div class="module-header">
                    <span class="drag-handle" draggable="true">☰</span>
                    <input type="text" class="module-title-input" value="${escapeHtml(mod.name)}" oninput="updateModuleName('${mod.id}', this.value)" placeholder="Module Name">
                    <span class="module-badge">${mod.features.length} Features</span>
                    <div class="module-actions">
                        <button type="button" class="btn-card-toggle" onclick="toggleModuleCollapse('${mod.id}')">${arrow}</button>
                        <button type="button" class="btn-remove-item" onclick="deleteScopeModule('${mod.id}')" style="padding: 4px 8px;">&times;</button>
                    </div>
                </div>
                
                <div class="module-body" style="${isCollapsed}">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label class="form-label" style="font-size: 11px;">Module Description (Optional)</label>
                        <input type="text" class="form-input" value="${escapeHtml(mod.description)}" oninput="updateModuleDesc('${mod.id}', this.value)" placeholder="Short description of this module..." style="color: var(--text-main); background: #f8fafc; font-size: 12px; height: 34px;">
                    </div>
                    
                    <div class="features-list sortable-list" data-mod-id="${mod.id}">
                        ${mod.features.map((feature, fIdx) => `
                            <div class="feature-item sortable-item" data-idx="${fIdx}">
                                <span class="drag-handle" draggable="true">☰</span>
                                <input type="text" class="feature-input" value="${escapeHtml(feature)}" oninput="updateFeatureName('${mod.id}', ${fIdx}, this.value)" placeholder="Feature description...">
                                <button type="button" class="btn-remove-item" onclick="deleteFeature('${mod.id}', ${fIdx})" style="padding: 4px 8px;">&times;</button>
                            </div>
                        `).join('')}
                    </div>
                    
                    <button type="button" class="btn-secondary" style="padding: 5px 12px; font-size: 12px; margin-top: 8px;" onclick="addFeatureToModule('${mod.id}')">+ Add Feature</button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    updateQuotationPreview();
}

function addScopeModule() {
    const id = 'mod_' + Date.now();
    quotationScopeModules.push({
        id: id,
        name: 'New Module',
        description: '',
        features: ['Core functionality'],
        isCollapsed: false
    });
    renderScopeModules();
}

function addPresetScopeModule(name) {
    const features = PRESET_MODULES[name] || ['Core functionality'];
    const id = 'mod_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5);
    quotationScopeModules.push({
        id: id,
        name: name,
        description: `Scope overview for ${name} modules.`,
        features: [...features],
        isCollapsed: false
    });
    renderScopeModules();
}

function deleteScopeModule(id) {
    if (confirm('Delete this entire module and all its features?')) {
        quotationScopeModules = quotationScopeModules.filter(m => m.id !== id);
        renderScopeModules();
    }
}

function toggleModuleCollapse(id) {
    const mod = quotationScopeModules.find(m => m.id === id);
    if (mod) {
        mod.isCollapsed = !mod.isCollapsed;
        renderScopeModules();
    }
}

function updateModuleName(id, val) {
    const mod = quotationScopeModules.find(m => m.id === id);
    if (mod) {
        mod.name = val;
        updateQuotationPreview();
    }
}

function updateModuleDesc(id, val) {
    const mod = quotationScopeModules.find(m => m.id === id);
    if (mod) {
        mod.description = val;
        updateQuotationPreview();
    }
}

function addFeatureToModule(modId) {
    const mod = quotationScopeModules.find(m => m.id === modId);
    if (mod) {
        mod.features.push('New Feature');
        renderScopeModules();
    }
}

function updateFeatureName(modId, fIdx, val) {
    const mod = quotationScopeModules.find(m => m.id === modId);
    if (mod && mod.features[fIdx] !== undefined) {
        mod.features[fIdx] = val;
        updateQuotationPreview();
    }
}

function deleteFeature(modId, fIdx) {
    const mod = quotationScopeModules.find(m => m.id === modId);
    if (mod) {
        mod.features.splice(fIdx, 1);
        renderScopeModules();
    }
}

// 6. Terms Editor Logic
function renderTermsList() {
    const container = document.getElementById('qTermsList');
    if (!container) return;
    
    let html = '';
    quotationTerms.forEach((term, index) => {
        html += `
            <div class="term-item sortable-item" data-idx="${index}">
                <div class="term-header">
                    <span class="drag-handle" draggable="true">☰</span>
                    <input type="text" class="term-title-input" value="${escapeHtml(term.title)}" oninput="updateTermTitle(${index}, this.value)" placeholder="Section Title">
                    <button type="button" class="btn-remove-item" onclick="deleteTerm(${index})" style="padding: 4px 8px;">&times;</button>
                </div>
                <textarea class="term-textarea" rows="2" oninput="updateTermContent(${index}, this.value)" placeholder="Describe terms...">HTML5 markup...</textarea>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Populate textareas manually to avoid raw tags rendering issues
    document.querySelectorAll('#qTermsList textarea').forEach((textarea, index) => {
        if (quotationTerms[index]) {
            textarea.value = quotationTerms[index].content;
        }
    });
    
    updateQuotationPreview();
}

function addTermRow() {
    quotationTerms.push({
        title: 'New Section',
        content: 'Enter terms and conditions here.'
    });
    renderTermsList();
}

function deleteTerm(idx) {
    quotationTerms.splice(idx, 1);
    renderTermsList();
}

function updateTermTitle(idx, val) {
    if (quotationTerms[idx]) {
        quotationTerms[idx].title = val;
        updateQuotationPreview();
    }
}

function updateTermContent(idx, val) {
    if (quotationTerms[idx]) {
        quotationTerms[idx].content = val;
        updateQuotationPreview();
    }
}

// 7. Live Preview Generator
function updateQuotationPreview() {
    return; // Disabled preview to prevent performance slowdown
    
    const clientName = document.getElementById('qClientName').value || 'Client Name';
    const clientPhone = document.getElementById('qClientPhone').value || 'Phone Number';
    const clientEmail = document.getElementById('qClientEmail').value || 'Email Address';
    const clientGst = document.getElementById('qClientGst').value || 'N/A';
    const clientAddress = document.getElementById('qClientAddress').value || '';
    
    const projectName = document.getElementById('qProjectName').value || 'Untitled Project';
    const projectDesc = document.getElementById('qProjectDescription').value || '';
    
    // Fetch Items Table Rows
    const items = [];
    document.querySelectorAll('#qItemsTableBody tr').forEach(row => {
        const desc = row.querySelector('.q-item-desc').value || 'Line Item';
        const rate = parseFloat(row.querySelector('.q-item-rate').value) || 0;
        items.push({ description: desc, qty: 1, rate: rate, amount: rate });
    });
    
    const totalAmt = items.reduce((acc, it) => acc + it.amount, 0);
    const discount = parseFloat(document.getElementById('qDiscountInput').value) || 0;
    const grandTotal = Math.max(0, totalAmt - discount);
    const gstPercent = parseFloat(document.getElementById('qGstRateInput').value) || 0;
    const subtotal = grandTotal / (1 + (gstPercent / 100));
    const gst = grandTotal - subtotal;

    // PAGE 1 HTML
    const page1Html = `
        <div class="preview-header">
            <div>
                <img src="assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="Logo" class="preview-logo" onerror="this.src='https://via.placeholder.com/120x35?text=TriShaKi'">
                <div class="preview-company-name">TRISHAKI TECHNOLOGIES PRIVATE LIMITED</div>
                <div class="preview-company-details">
                    F1, First Floor, Star Tower, RPD Circle,<br>
                    Opposite Canara Bank, Tilakwadi,<br>
                    Belagavi, Karnataka - 590006<br>
                    <strong>Phone:</strong> +91 9980681304 | <strong>Email:</strong> info@trishaki.com
                </div>
            </div>
            <div class="preview-title-area">
                <h1>QUOTATION</h1>
                <div class="preview-meta">
                    QUOTATION NO: <span>QT-2026-XXX (Auto)</span><br>
                    DATE: <span>${formattedDate}</span><br>
                    VALID UNTIL: <span>${validUntilDate}</span>
                </div>
            </div>
        </div>
        
        <div class="preview-addresses">
            <div class="preview-address-box">
                <h3>Quotation For</h3>
                <strong>${escapeHtml(clientName)}</strong>
                <p>
                    ${clientAddress ? escapeHtml(clientAddress) + '<br>' : ''}
                    <strong>Phone:</strong> ${escapeHtml(clientPhone)}<br>
                    <strong>Email:</strong> ${escapeHtml(clientEmail)}<br>
                    <strong>GSTIN:</strong> ${escapeHtml(clientGst)}
                </p>
            </div>
            <div class="preview-address-box">
                <h3>Project Overview</h3>
                <p>
                    <strong>Project:</strong> ${escapeHtml(projectName)}<br>
                    <strong>Description:</strong> ${escapeHtml(projectDesc || 'N/A')}
                </p>
            </div>
        </div>
        
        <table class="preview-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">#</th>
                    <th>Description</th>
                    <th style="width: 150px; text-align: right;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                ${items.map((it, idx) => `
                    <tr>
                        <td style="text-align: center;">${idx + 1}</td>
                        <td>${escapeHtml(it.description)}</td>
                        <td style="text-align: right;">${it.amount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
        
        <div class="preview-summary-block">
            <table class="preview-summary-table">
                ${discount > 0 ? `
                <tr>
                    <td>Total Amount:</td>
                    <td style="text-align: right;">₹${totalAmt.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                </tr>
                <tr>
                    <td>Discount:</td>
                    <td style="text-align: right; color: #ef4444;">-₹${discount.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                </tr>
                ` : ''}
                <tr class="total-row">
                    <td>Grand Total:</td>
                    <td style="text-align: right;">₹${grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                </tr>
            </table>
        </div>
        
        <div class="preview-terms">
            <h4>Terms & Conditions</h4>
            ${quotationTerms.map(t => {
                if (/^\d+$/.test(t.title)) {
                    return `
                        <div class="preview-term-section">
                            <strong>${escapeHtml(t.title)}.</strong> ${escapeHtml(t.content)}
                        </div>
                    `;
                } else {
                    return `
                        <div class="preview-term-section">
                            <strong>${escapeHtml(t.title)}:</strong> ${escapeHtml(t.content)}
                        </div>
                    `;
                }
            }).join('')}
        </div>
        
        <div class="preview-signatures">
            <div class="preview-sig-col">
                <div style="border: 1px dashed #cbd5e1; height: 55px; width: 100%; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 8px; border-radius: 4px; background: #fafafa;">
                    Client Seal / Signature Box
                </div>
                <div class="preview-sig-line"></div>
                <span class="preview-sig-label">Accepted By</span>
                ${(document.getElementById('qClientSignName') && document.getElementById('qClientSignName').value) ? `<div style="font-size: 9px; color: #334155; font-weight: bold;">${escapeHtml(document.getElementById('qClientSignName').value)}</div>` : ''}
                ${(document.getElementById('qClientSignDate') && document.getElementById('qClientSignDate').value) ? `<div style="font-size: 8px; color: #64748b;">Date: ${escapeHtml(document.getElementById('qClientSignDate').value)}</div>` : ''}
            </div>
            
            <div class="preview-sig-col" style="position: relative;">
                <div style="height: 55px; display: flex; align-items: flex-end; justify-content: center;">
                    <img src="assets/ningaraj_sign_blue.png" alt="Company Sign" style="height: 60px; max-width: 130px; object-fit: contain; margin-bottom: -10px; transform: rotate(-2deg);" onerror="this.style.display='none'">
                </div>
                <div class="preview-sig-line"></div>
                <span class="preview-sig-label">Prepared By (Authorized Signatory)</span>
                <div style="font-size: 8px; color: #64748b; font-weight: bold; text-transform: uppercase;">TriShaKi Technologies</div>
            </div>
        </div>
    `;
    
    document.getElementById('previewPage1').innerHTML = page1Html;
    
    // PAGE 2 HTML: Scope of Work
    const includeScope = document.getElementById('qIncludeScope') ? document.getElementById('qIncludeScope').checked : true;
    const page2Element = document.getElementById('previewPage2');
    
    if (!includeScope) {
        if (page2Element) page2Element.style.display = 'none';
    } else {
        if (page2Element) page2Element.style.display = 'block';
        
        let page2Html = `
            <div class="preview-header">
                <div>
                    <img src="assets/TRISHAKI LOGO TRANSPERANT BG.png" alt="Logo" class="preview-logo" onerror="this.src='https://via.placeholder.com/120x35?text=TriShaKi'">
                    <div class="preview-company-name">TRISHAKI TECHNOLOGIES PRIVATE LIMITED</div>
                </div>
                <div class="preview-title-area">
                    <h1>PROJECT SCOPE</h1>
                    <div class="preview-meta">
                        PROJECT: <span>${escapeHtml(projectName)}</span>
                    </div>
                </div>
            </div>
            
            <div class="preview-scope-title">Scope of Work & Features List</div>
        `;
        
        if (quotationScopeModules.length === 0) {
            page2Html += `
                <div style="text-align: center; padding: 80px 0; color: #94a3b8; font-style: italic;">
                    Scope builder has no modules added yet. Add modules in Tab 3 to display features.
                </div>
            `;
        } else {
            quotationScopeModules.forEach(mod => {
                page2Html += `
                    <div class="preview-module-item">
                        <div class="preview-module-name">
                            <span>${escapeHtml(mod.name)}</span>
                        </div>
                        ${mod.description ? `<div class="preview-module-desc">${escapeHtml(mod.description)}</div>` : ''}
                        <div class="preview-features-grid">
                            ${mod.features.map(f => `
                                <div class="preview-feature-bullet">${escapeHtml(f)}</div>
                            `).join('')}
                        </div>
                    </div>
                `;
            });
        }
        
        if (page2Element) page2Element.innerHTML = page2Html;
    }
}

// 8. Drag and Drop Sorting Helper
function initQuotationDragAndDrop() {
    // 1. Drag and drop for items table rows
    const tableBody = document.getElementById('qItemsTableBody');
    if (tableBody) {
        tableBody.addEventListener('dragstart', handleRowDragStart);
        tableBody.addEventListener('dragover', handleRowDragOver);
        tableBody.addEventListener('dragend', handleRowDragEnd);
    }
    
    // 2. Drag and drop for Scope modules cards
    const modulesList = document.getElementById('qScopeModulesList');
    if (modulesList) {
        modulesList.addEventListener('dragstart', handleModuleDragStart);
        modulesList.addEventListener('dragover', handleModuleDragOver);
        modulesList.addEventListener('dragend', handleModuleDragEnd);
    }
    
    // 3. Drag and drop for Terms list cards
    const termsList = document.getElementById('qTermsList');
    if (termsList) {
        termsList.addEventListener('dragstart', handleTermDragStart);
        termsList.addEventListener('dragover', handleTermDragOver);
        termsList.addEventListener('dragend', handleTermDragEnd);
    }
}

// Helper: drag over calculations
function getDragAfterElement(container, y, selector) {
    const draggableElements = [...container.querySelectorAll(selector + ':not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// Table Rows Drag & Drop
let draggedRow = null;
function handleRowDragStart(e) {
    const handle = e.target.closest('.drag-handle');
    if (!handle) {
        e.preventDefault();
        return;
    }
    draggedRow = e.target.closest('tr');
    if (draggedRow) {
        draggedRow.classList.add('dragging');
    }
}
function handleRowDragOver(e) {
    if (!draggedRow) return;
    e.preventDefault();
    const tableBody = document.getElementById('qItemsTableBody');
    const afterElement = getDragAfterElement(tableBody, e.clientY, 'tr');
    if (afterElement == null) {
        tableBody.appendChild(draggedRow);
    } else {
        tableBody.insertBefore(draggedRow, afterElement);
    }
}
function handleRowDragEnd(e) {
    if (draggedRow) {
        draggedRow.classList.remove('dragging');
        draggedRow = null;
    }
    calculateQuotationSummaryNew();
}

// Scope Modules Drag & Drop
let draggedModule = null;
function handleModuleDragStart(e) {
    const handle = e.target.closest('.drag-handle');
    if (!handle) {
        e.preventDefault();
        return;
    }
    draggedModule = e.target.closest('.module-card');
    if (draggedModule) {
        draggedModule.classList.add('dragging');
    }
}
function handleModuleDragOver(e) {
    if (!draggedModule) return;
    e.preventDefault();
    const list = document.getElementById('qScopeModulesList');
    const afterElement = getDragAfterElement(list, e.clientY, '.module-card');
    if (afterElement == null) {
        list.appendChild(draggedModule);
    } else {
        list.insertBefore(draggedModule, afterElement);
    }
}
function handleModuleDragEnd(e) {
    if (draggedModule) {
        draggedModule.classList.remove('dragging');
        draggedModule = null;
    }
    rebuildScopeModulesArrayFromDOM();
}

// Terms List Drag & Drop
let draggedTerm = null;
function handleTermDragStart(e) {
    const handle = e.target.closest('.drag-handle');
    if (!handle) {
        e.preventDefault();
        return;
    }
    draggedTerm = e.target.closest('.term-item');
    if (draggedTerm) {
        draggedTerm.classList.add('dragging');
    }
}
function handleTermDragOver(e) {
    if (!draggedTerm) return;
    e.preventDefault();
    const list = document.getElementById('qTermsList');
    const afterElement = getDragAfterElement(list, e.clientY, '.term-item');
    if (afterElement == null) {
        list.appendChild(draggedTerm);
    } else {
        list.insertBefore(draggedTerm, afterElement);
    }
}
function handleTermDragEnd(e) {
    if (draggedTerm) {
        draggedTerm.classList.remove('dragging');
        draggedTerm = null;
    }
    rebuildTermsArrayFromDOM();
}

// Rebuilders
function rebuildScopeModulesArrayFromDOM() {
    const newModules = [];
    document.querySelectorAll('#qScopeModulesList .module-card').forEach(card => {
        const id = card.getAttribute('data-id');
        const oldMod = quotationScopeModules.find(m => m.id === id);
        if (oldMod) {
            // Find features inside this card
            const features = [];
            card.querySelectorAll('.feature-input').forEach(input => {
                features.push(input.value);
            });
            newModules.push({
                id: id,
                name: card.querySelector('.module-title-input').value,
                description: oldMod.description,
                features: features,
                isCollapsed: oldMod.isCollapsed
            });
        }
    });
    quotationScopeModules = newModules;
    updateQuotationPreview();
}

function rebuildTermsArrayFromDOM() {
    const list = document.getElementById('qTermsList');
    if (!list || list.offsetParent === null) {
        return; // Keep existing quotationTerms
    }
    const newTerms = [];
    document.querySelectorAll('#qTermsList .term-item').forEach(item => {
        const title = item.querySelector('.term-title-input').value;
        const content = item.querySelector('.term-textarea').value;
        newTerms.push({ title: title, content: content });
    });
    if (newTerms.length > 0) {
        quotationTerms = newTerms;
    }
    updateQuotationPreview();
}

// 9. Save & Drafts Logic
function saveQuotationNew(statusType = 'draft') {
    const items = [];
    document.querySelectorAll('#qItemsTableBody tr').forEach(row => {
        const desc = row.querySelector('.q-item-desc').value;
        const rate = parseFloat(row.querySelector('.q-item-rate').value) || 0;
        items.push({
            description: desc,
            qty: 1,
            rate: rate,
            amount: rate
        });
    });
    
    if (items.length === 0) {
        alert('Please add at least one item to the quotation');
        return;
    }
    
    // Prepare full structured items payload
    const totalAmt = items.reduce((acc, it) => acc + it.amount, 0);
    const discount = parseFloat(document.getElementById('qDiscountInput').value) || 0;
    const grandTotal = Math.max(0, totalAmt - discount);
    const gstPercent = parseFloat(document.getElementById('qGstRateInput').value) || 0;
    
    // Collect Scope
    rebuildScopeModulesArrayFromDOM();
    
    // Collect Terms
    rebuildTermsArrayFromDOM();
    
    const payloadItems = {
        commercial_items: items,
        project_name: document.getElementById('qProjectName').value || 'Project Proposal',
        project_description: document.getElementById('qProjectDescription').value || '',
        discount: discount,
        gst_percent: gstPercent,
        include_scope: document.getElementById('qIncludeScope') ? document.getElementById('qIncludeScope').checked : true,
        scope_of_work: quotationScopeModules.map(m => ({
            module_name: m.name,
            description: m.description,
            features: m.features
        })),
        terms: quotationTerms,
        client_signature_name: document.getElementById('qClientSignName') ? document.getElementById('qClientSignName').value : '',
        client_signature_date: document.getElementById('qClientSignDate') ? document.getElementById('qClientSignDate').value : ''
    };
    
    // Save/Update quotation
    const quotationNo = document.getElementById('quotationId').value || null;
    
    const payload = {
        quotationNo: quotationNo,
        clientName: document.getElementById('qClientName').value,
        phone: document.getElementById('qClientPhone').value,
        email: document.getElementById('qClientEmail').value,
        gstNumber: document.getElementById('qClientGst').value,
        address: document.getElementById('qClientAddress').value,
        date: document.getElementById('qDate').value,
        items: payloadItems, // Store the structured object in items column
        totalAmount: grandTotal,
        status: statusType
    };
    
    if (!payload.clientName || !payload.phone) {
        alert('Client Name and Phone number are required fields.');
        return;
    }
    
    const submitBtn = document.getElementById('btnQuotationSubmit');
    const draftBtn = document.getElementById('btnQuotationSaveDraft');
    if (submitBtn) submitBtn.disabled = true;
    if (draftBtn) draftBtn.disabled = true;
    
    fetch('api/save_quotation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (submitBtn) submitBtn.disabled = false;
        if (draftBtn) draftBtn.disabled = false;
        
        if (data.success) {
            alert('Quotation saved successfully! Quotation Number: ' + data.quotationNo);
            closeQuotationModal();
            if (typeof loadQuotations !== 'undefined') {
                loadQuotations();
            }
            if (statusType === 'sent') {
                // Instantly trigger print page!
                window.open('api/print_quotation.php?id=' + data.id || '', '_blank');
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to save quotation'));
        }
    })
    .catch(err => {
        if (submitBtn) submitBtn.disabled = false;
        if (draftBtn) draftBtn.disabled = false;
        console.error('Error saving quotation:', err);
    });
}

// 10. Duplicate / Re-use Quotation
function duplicateQuotation(id) {
    if (confirm('Create a duplicate copy of this quotation?')) {
        fetch('api/get_quotations.php')
            .then(res => res.json())
            .then(quotes => {
                const quote = quotes.find(q => q.id === parseInt(id));
                if (quote) {
                    // Open builder with this information but clear quotation ID (creates new)
                    openQuotationModal(quote.quotationNo);
                    document.getElementById('quotationId').value = '';
                    document.getElementById('quotationModalTitle').textContent = 'Duplicate Quotation';
                    updateQuotationPreview();
                }
            });
    }
}

// Utilities
function escapeHtml(string) {
    if (!string) return '';
    return String(string)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}
