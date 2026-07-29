<?php
$content = file_get_contents('js/app.js');

$old_thead = '<tr>
                        <th>ID</th>
                        <th>Client Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GST Number</th>
                        <th>Invoices</th>
                        <th>Last Invoice Date</th>
                        <th>Actions</th>
                    </tr>';

$new_thead = '<tr>
                        <th>S.No</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>GST Number</th>
                        <th>Invoices</th>
                        <th>Last Invoice Date</th>
                        <th>Actions</th>
                    </tr>';

$content = str_replace($old_thead, $new_thead, $content);

$old_colspan = 'html += \'<tr><td colspan="8" class="text-center" style="color: var(--text-muted); padding: 30px;">No clients available.</td></tr>\';';
$new_colspan = 'html += \'<tr><td colspan="9" class="text-center" style="color: var(--text-muted); padding: 30px;">No clients available.</td></tr>\';';

$content = str_replace($old_colspan, $new_colspan, $content);

$old_tbody = 'data.forEach(client => {
                    const lastDate = client.lastInvoiceDate 
                        ? new Date(client.lastInvoiceDate).toLocaleDateString(\'en-IN\', {day: \'2-digit\', month: \'short\', year: \'numeric\'})
                        : \'No Invoices\';
                        
                    html += `
                        <tr>
                            <td><strong>#${client.id}</strong></td>
                            <td>${client.name}</td>
                            <td>${client.phone}</td>';

$new_tbody = 'let sNo = 1;
                data.forEach(client => {
                    const lastDate = client.lastInvoiceDate 
                        ? new Date(client.lastInvoiceDate).toLocaleDateString(\'en-IN\', {day: \'2-digit\', month: \'short\', year: \'numeric\'})
                        : \'No Invoices\';
                        
                    html += `
                        <tr>
                            <td><strong>${sNo++}</strong></td>
                            <td>${client.name}</td>
                            <td><span style="background: ${client.client_type === \'Student\' ? \'#eff6ff\' : \'#f8fafc\'}; color: ${client.client_type === \'Student\' ? \'#3b82f6\' : \'#64748b\'}; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; border: 1px solid ${client.client_type === \'Student\' ? \'#bfdbfe\' : \'#e2e8f0\'};">${client.client_type || \'Client\'}</span></td>
                            <td>${client.phone}</td>';

$content = str_replace($old_tbody, $new_tbody, $content);

file_put_contents('js/app.js', $content);

echo "Updated Clients Table Columns.";
