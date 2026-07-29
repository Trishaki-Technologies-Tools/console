<?php
$content = file_get_contents('js/app.js');

// 1. Replace thead
$pattern_thead = '/<tr>\s*<th>ID<\/th>\s*<th>Client Name<\/th>\s*<th>Phone<\/th>\s*<th>Email<\/th>\s*<th>GST Number<\/th>\s*<th>Invoices<\/th>\s*<th>Last Invoice Date<\/th>\s*<th>Actions<\/th>\s*<\/tr>/s';
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
$content = preg_replace($pattern_thead, $new_thead, $content);

// 2. Replace tbody
$pattern_tbody = '/data\.forEach\(client => \{\s*const lastDate = client\.lastInvoiceDate\s*\?\s*new Date\(client\.lastInvoiceDate\)\.toLocaleDateString\(\'en-IN\', \{day: \'2-digit\', month: \'short\', year: \'numeric\'\}\)\s*:\s*\'No Invoices\';\s*html \+= `\s*<tr>\s*<td><strong>#\$\{client\.id\}<\/strong><\/td>\s*<td>\$\{client\.name\}<\/td>\s*<td>\$\{client\.phone\}<\/td>/s';

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

$content = preg_replace($pattern_tbody, $new_tbody, $content);

file_put_contents('js/app.js', $content);

echo "Updated via regex.";
