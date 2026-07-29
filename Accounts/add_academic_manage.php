<?php
$content = file_get_contents('index.php');

$old_student_fields = '<div id="studentSpecificFields" style="display: none;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">College Name</label>
                            <input type="text" id="newClientCollege" class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Department</label>
                            <input type="text" id="newClientDepartment" class="form-input">
                        </div>
                    </div>';

$new_student_fields = '<div id="studentSpecificFields" style="display: none;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label class="form-label" style="margin-bottom: 0;">College Name</label>
                                <a href="#" onclick="openManageAcademicModal(\'colleges\'); return false;" style="font-size: 12px; color: #3b82f6; text-decoration: none;">Manage</a>
                            </div>
                            <select id="newClientCollege" class="form-input">
                                <option value="">Select College</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <label class="form-label" style="margin-bottom: 0;">Department</label>
                                <a href="#" onclick="openManageAcademicModal(\'departments\'); return false;" style="font-size: 12px; color: #3b82f6; text-decoration: none;">Manage</a>
                            </div>
                            <select id="newClientDepartment" class="form-input">
                                <option value="">Select Department</option>
                            </select>
                        </div>
                    </div>';

$content = str_replace($old_student_fields, $new_student_fields, $content);

$manage_modal = '
    <!-- Manage Academic Modal -->
    <div id="manageAcademicModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 id="manageAcademicTitle">Manage Records</h3>
                <button class="modal-close" onclick="closeManageAcademicModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addAcademicForm" onsubmit="addAcademicItem(event)" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="hidden" id="manageAcademicType">
                    <input type="text" id="newAcademicName" class="form-input" placeholder="Enter name to add..." required style="flex: 1;">
                    <button type="submit" class="btn-primary">Add</button>
                </form>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
                                <th style="padding: 10px; text-align: left; font-size: 13px; color: #64748b;">Name</th>
                                <th style="padding: 10px; text-align: right; font-size: 13px; color: #64748b; width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="manageAcademicList">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
';

$content = preg_replace('/<\/body>(?!.*<\/body>)/s', $manage_modal . "\n</body>", $content);

file_put_contents('index.php', $content);

// 2. JS Updates
$app_js = file_get_contents('js/app.js');

$new_js = "
function loadAcademicDropdowns() {
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
}

// Call on load
document.addEventListener('DOMContentLoaded', () => {
    loadAcademicDropdowns();
});

function openManageAcademicModal(type) {
    document.getElementById('manageAcademicType').value = type;
    document.getElementById('manageAcademicTitle').textContent = type === 'colleges' ? 'Manage Colleges' : 'Manage Departments';
    loadAcademicList(type);
    document.getElementById('manageAcademicModal').classList.add('show');
}

function closeManageAcademicModal() {
    document.getElementById('manageAcademicModal').classList.remove('show');
    document.getElementById('addAcademicForm').reset();
}

function loadAcademicList(type) {
    const tbody = document.getElementById('manageAcademicList');
    tbody.innerHTML = '<tr><td colspan=\"2\" style=\"padding: 15px; text-align: center; color: #64748b;\">Loading...</td></tr>';
    
    fetch('api/manage_academic.php?action=get&type=' + type)
        .then(res => res.json())
        .then(data => {
            if (!data || data.error) {
                tbody.innerHTML = '<tr><td colspan=\"2\" style=\"padding: 15px; text-align: center; color: #ef4444;\">Failed to load data</td></tr>';
                return;
            }
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan=\"2\" style=\"padding: 15px; text-align: center; color: #64748b;\">No records found</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            data.forEach(item => {
                tbody.innerHTML += `
                    <tr style=\"border-bottom: 1px solid #f1f5f9;\">
                        <td style=\"padding: 10px; font-size: 14px;\">\${item.name}</td>
                        <td style=\"padding: 10px; text-align: right;\">
                            <button type=\"button\" onclick=\"deleteAcademicItem(\${item.id}, '\${type}')\" style=\"background: none; border: none; color: #ef4444; cursor: pointer; font-size: 12px;\">Delete</button>
                        </td>
                    </tr>
                `;
            });
        });
}

function addAcademicItem(e) {
    e.preventDefault();
    const type = document.getElementById('manageAcademicType').value;
    const name = document.getElementById('newAcademicName').value;
    
    const data = new FormData();
    data.append('action', 'add');
    data.append('type', type);
    data.append('name', name);
    
    fetch('api/manage_academic.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                document.getElementById('newAcademicName').value = '';
                loadAcademicList(type);
                loadAcademicDropdowns();
            } else {
                alert(res.error || 'Failed to add');
            }
        });
}

function deleteAcademicItem(id, type) {
    if(!confirm('Are you sure you want to delete this record?')) return;
    
    const data = new FormData();
    data.append('action', 'delete');
    data.append('type', type);
    data.append('id', id);
    
    fetch('api/manage_academic.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                loadAcademicList(type);
                loadAcademicDropdowns();
            } else {
                alert(res.error || 'Failed to delete');
            }
        });
}
";

if (strpos($app_js, 'function openManageAcademicModal') === false) {
    $app_js .= "\n" . $new_js;
    file_put_contents('js/app.js', $app_js);
}

echo "Added Management UI and JS.";
