<?php
$content = file_get_contents('index.php');

// 1. Add datalist to index.php
if (strpos($content, '<datalist id="clientDatalist">') === false) {
    $content = str_replace('</body>', '<datalist id="clientDatalist"></datalist></body>', $content);
}

// 2. Add list attribute to GST Bill To Name
$content = str_replace('<input type="text" id="gstBillToName" class="form-input" placeholder="Enter customer name"',
'<input type="text" id="gstBillToName" class="form-input" placeholder="Enter customer name or select from list" list="clientDatalist" oninput="autofillClientDetails(\'gst\')"',
$content);

// 3. Add list attribute to Non-GST Bill To Name
$content = str_replace('<input type="text" id="nonGstBillToName" class="form-input" placeholder="Enter customer name"',
'<input type="text" id="nonGstBillToName" class="form-input" placeholder="Enter customer name or select from list" list="clientDatalist" oninput="autofillClientDetails(\'nonGst\')"',
$content);

// 4. Add "Add Client" button to Clients Page
$clients_header = '<h2>Clients CRM & Directory</h2>
                            <p class="company-subtitle">View active clients, invoice history and GST information</p>
                        </div>';
$clients_header_new = '<h2>Clients CRM & Directory</h2>
                            <p class="company-subtitle">View active clients, invoice history and GST information</p>
                        </div>
                        <div class="filter-row">
                            <button class="btn-primary" onclick="openAddClientModal()">+ Add Client</button>
                        </div>';
$content = str_replace($clients_header, $clients_header_new, $content);

// 5. Add "Add Client" Modal
$add_client_modal = '
    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Add New Client</h3>
                <button class="modal-close" onclick="closeAddClientModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addClientForm" onsubmit="saveNewClient(event)">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label">Client Name <span class="required">*</span></label>
                        <input type="text" id="newClientName" class="form-input" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="newClientPhone" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" id="newClientEmail" class="form-input">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label">GST Number</label>
                        <input type="text" id="newClientGst" class="form-input">
                    </div>
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">Billing Address</label>
                        <textarea id="newClientAddress" class="form-input" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%;">Save Client</button>
                </form>
            </div>
        </div>
    </div>
</body>';
$content = str_replace('</body>', $add_client_modal, $content);

file_put_contents('index.php', $content);
echo "index.php updated successfully.";
