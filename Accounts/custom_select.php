<?php
$content = file_get_contents('index.php');

// 1. Add Custom Select CSS before the modal
$custom_select_css = '<style>
    .custom-select-wrapper { position: relative; user-select: none; }
    .custom-select-display { border: 1px solid #cbd5e1; padding: 10px 15px; border-radius: 6px; background: #fff; cursor: pointer; font-size: 14px; color: #0f172a; display: flex; justify-content: space-between; align-items: center; }
    .custom-select-display::after { content: "▼"; font-size: 10px; color: #64748b; }
    .custom-select-options { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; margin-top: 4px; max-height: 160px; overflow-y: auto; z-index: 50; display: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .custom-select-options.show { display: block; }
    .custom-select-option { padding: 10px 15px; cursor: pointer; font-size: 14px; color: #0f172a; border-bottom: 1px solid #f1f5f9; }
    .custom-select-option:last-child { border-bottom: none; }
    .custom-select-option:hover { background: #f8fafc; color: #3b82f6; }
</style>';

$old_college_select = '<select id="newClientCollege" class="form-input">
                                <option value="">Select College</option>
                            </select>';
                            
$new_college_select = $custom_select_css . '
                            <div class="custom-select-wrapper">
                                <input type="hidden" id="newClientCollege" value="">
                                <div class="custom-select-display" id="collegeDisplay" onclick="toggleCustomSelect(\'collegeOptions\')">Select College</div>
                                <div class="custom-select-options hidden-scrollbar" id="collegeOptions">
                                    <div class="custom-select-option" onclick="selectCustomOption(\'collegeOptions\', \'newClientCollege\', \'collegeDisplay\', \'\', \'Select College\')">Select College</div>
                                </div>
                            </div>';

$content = str_replace($old_college_select, $new_college_select, $content);

$old_dept_select = '<select id="newClientDepartment" class="form-input">
                                <option value="">Select Department</option>
                            </select>';

$new_dept_select = '<div class="custom-select-wrapper">
                                <input type="hidden" id="newClientDepartment" value="">
                                <div class="custom-select-display" id="deptDisplay" onclick="toggleCustomSelect(\'deptOptions\')">Select Department</div>
                                <div class="custom-select-options hidden-scrollbar" id="deptOptions">
                                    <div class="custom-select-option" onclick="selectCustomOption(\'deptOptions\', \'newClientDepartment\', \'deptDisplay\', \'\', \'Select Department\')">Select Department</div>
                                </div>
                            </div>';

$content = str_replace($old_dept_select, $new_dept_select, $content);
file_put_contents('index.php', $content);

// 2. Update js/app.js
$app_js = file_get_contents('js/app.js');

$new_js_logic = "
// Custom Select Logic
function toggleCustomSelect(id) {
    document.querySelectorAll('.custom-select-options').forEach(el => {
        if (el.id !== id) el.classList.remove('show');
    });
    document.getElementById(id).classList.toggle('show');
}

function selectCustomOption(optionsId, hiddenId, displayId, value, text) {
    document.getElementById(hiddenId).value = value;
    document.getElementById(displayId).textContent = text;
    document.getElementById(optionsId).classList.remove('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-select-wrapper')) {
        document.querySelectorAll('.custom-select-options').forEach(el => el.classList.remove('show'));
    }
});
";

if (strpos($app_js, 'function toggleCustomSelect') === false) {
    $app_js .= "\n" . $new_js_logic;
}

// Modify loadAcademicDropdowns to populate the custom divs instead of select options
$old_load_dropdowns = "function loadAcademicDropdowns() {
    ['colleges', 'departments'].forEach(type => {
        fetch('api/manage_academic.php?action=get&type=' + type)
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById(type === 'colleges' ? 'newClientCollege' : 'newClientDepartment');
                if (select) {
                    select.innerHTML = '<option value=\"\">Select ' + (type === 'colleges' ? 'College' : 'Department') + '</option>';
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.name;
                        opt.textContent = item.name;
                        select.appendChild(opt);
                    });
                }
            });
    });
}";

$new_load_dropdowns = "function loadAcademicDropdowns() {
    ['colleges', 'departments'].forEach(type => {
        fetch('api/manage_academic.php?action=get&type=' + type)
            .then(res => res.json())
            .then(data => {
                const isCollege = type === 'colleges';
                const optionsId = isCollege ? 'collegeOptions' : 'deptOptions';
                const hiddenId = isCollege ? 'newClientCollege' : 'newClientDepartment';
                const displayId = isCollege ? 'collegeDisplay' : 'deptDisplay';
                const defaultText = isCollege ? 'Select College' : 'Select Department';
                
                const optionsContainer = document.getElementById(optionsId);
                if (optionsContainer) {
                    optionsContainer.innerHTML = `<div class=\"custom-select-option\" onclick=\"selectCustomOption('\${optionsId}', '\${hiddenId}', '\${displayId}', '', '\${defaultText}')\">\${defaultText}</div>`;
                    data.forEach(item => {
                        // Escape single quotes for inline onclick
                        const safeName = item.name.replace(/'/g, \"\\\\'\");
                        optionsContainer.innerHTML += `<div class=\"custom-select-option\" onclick=\"selectCustomOption('\${optionsId}', '\${hiddenId}', '\${displayId}', '\${safeName}', '\${safeName}')\">\${item.name}</div>`;
                    });
                }
            });
    });
}";

$app_js = str_replace($old_load_dropdowns, $new_load_dropdowns, $app_js);

// Reset custom dropdowns when closing modal
$old_close_client = "function closeAddClientModal() {
    document.getElementById('addClientModal').classList.remove('show');
    document.getElementById('addClientForm').reset();
    if(typeof setClientType === 'function') setClientType('Client');
}";

$new_close_client = "function closeAddClientModal() {
    document.getElementById('addClientModal').classList.remove('show');
    document.getElementById('addClientForm').reset();
    if(typeof setClientType === 'function') setClientType('Client');
    if(document.getElementById('collegeDisplay')) document.getElementById('collegeDisplay').textContent = 'Select College';
    if(document.getElementById('deptDisplay')) document.getElementById('deptDisplay').textContent = 'Select Department';
}";

$app_js = str_replace($old_close_client, $new_close_client, $app_js);

file_put_contents('js/app.js', $app_js);

echo "Custom select implemented.";
