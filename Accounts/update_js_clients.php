<?php
$content = file_get_contents('js/app.js');

// 1. Store globally and populate datalist
$load_clients_old = "function loadClients() {
    fetch('api/get_clients.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            const tbody = document.getElementById('clients-list-container');";

$load_clients_new = "window.allClients = [];
function loadClients() {
    fetch('api/get_clients.php')
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            window.allClients = data;
            
            // Populate datalist for invoices
            const dl = document.getElementById('clientDatalist');
            if (dl) {
                dl.innerHTML = '';
                data.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.name;
                    dl.appendChild(opt);
                });
            }

            const tbody = document.getElementById('clients-list-container');";

$content = str_replace($load_clients_old, $load_clients_new, $content);

// 2. Add client modal functions
$add_client_funcs = "
function openAddClientModal() {
    document.getElementById('addClientModal').classList.add('show');
}
function closeAddClientModal() {
    document.getElementById('addClientModal').classList.remove('show');
    document.getElementById('addClientForm').reset();
}
function saveNewClient(e) {
    e.preventDefault();
    const data = new FormData();
    data.append('name', document.getElementById('newClientName').value);
    data.append('phone', document.getElementById('newClientPhone').value);
    data.append('email', document.getElementById('newClientEmail').value);
    data.append('gst_number', document.getElementById('newClientGst').value);
    data.append('address', document.getElementById('newClientAddress').value);

    fetch('api/add_client.php', {
        method: 'POST',
        body: data
    }).then(res => res.json()).then(res => {
        if(res.success) {
            closeAddClientModal();
            loadClients();
            alert('Client added successfully');
        } else {
            alert(res.error || 'Failed to add client');
        }
    });
}
";

$content .= "\n" . $add_client_funcs;

file_put_contents('js/app.js', $content);

// 3. Add autofill function to invoice_functions.js
$inv_content = file_get_contents('js/invoice_functions.js');
$autofill_func = "
function autofillClientDetails(type) {
    if (!window.allClients) return;
    const nameVal = document.getElementById(type + 'BillToName').value;
    const client = window.allClients.find(c => c.name === nameVal);
    
    if (client) {
        document.getElementById(type + 'Phone').value = client.phone || '';
        document.getElementById(type + 'Email').value = client.email && client.email !== 'N/A' ? client.email : '';
        document.getElementById(type + 'Address').value = client.address || '';
        
        if (type === 'gst' && document.getElementById('gstModalNumber')) {
            document.getElementById('gstModalNumber').value = client.gstNumber && client.gstNumber !== 'Not Applicable' ? client.gstNumber : '';
        }
    }
}
";
$inv_content .= "\n" . $autofill_func;
file_put_contents('js/invoice_functions.js', $inv_content);

echo "JS files updated.";
