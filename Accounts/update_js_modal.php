<?php
$content = file_get_contents('js/app.js');

// 1. Add toggleClientFields()
$toggle_func = "
function toggleClientFields() {
    const type = document.getElementById('newClientType').value;
    const clientFields = document.getElementById('clientSpecificFields');
    const studentFields = document.getElementById('studentSpecificFields');
    const nameLabel = document.getElementById('nameLabel');

    if (type === 'Student') {
        clientFields.style.display = 'none';
        studentFields.style.display = 'block';
        nameLabel.innerHTML = 'Student Name <span class=\"required\">*</span>';
    } else {
        clientFields.style.display = 'block';
        studentFields.style.display = 'none';
        nameLabel.innerHTML = 'Client Name <span class=\"required\">*</span>';
    }
}
";

// Avoid adding it multiple times
if (strpos($content, 'function toggleClientFields()') === false) {
    $content .= "\n" . $toggle_func;
}

// 2. Update saveNewClient function
$old_save = "function saveNewClient(e) {
    e.preventDefault();
    const data = new FormData();
    data.append('name', document.getElementById('newClientName').value);
    data.append('phone', document.getElementById('newClientPhone').value);
    data.append('email', document.getElementById('newClientEmail').value);
    data.append('gst_number', document.getElementById('newClientGst').value);
    data.append('address', document.getElementById('newClientAddress').value);";

$new_save = "function saveNewClient(e) {
    e.preventDefault();
    const data = new FormData();
    data.append('name', document.getElementById('newClientName').value);
    data.append('phone', document.getElementById('newClientPhone').value);
    data.append('email', document.getElementById('newClientEmail').value);
    data.append('client_type', document.getElementById('newClientType').value);
    
    if (document.getElementById('newClientType').value === 'Student') {
        data.append('college_name', document.getElementById('newClientCollege').value);
        data.append('department', document.getElementById('newClientDepartment').value);
        data.append('gst_number', '');
        data.append('address', '');
    } else {
        data.append('gst_number', document.getElementById('newClientGst').value);
        data.append('address', document.getElementById('newClientAddress').value);
        data.append('college_name', '');
        data.append('department', '');
    }";

$content = str_replace($old_save, $new_save, $content);

file_put_contents('js/app.js', $content);
echo "js/app.js updated with new save logic.";
