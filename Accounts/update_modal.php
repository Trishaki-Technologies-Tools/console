<?php
$content = file_get_contents('index.php');

// Remove all occurrences of the Add Client Modal to clean up duplicates
$pattern = '/<!-- Add Client Modal -->.*?<\/form>\s*<\/div>\s*<\/div>\s*<\/div>/s';
$content = preg_replace($pattern, '', $content);

// Create the new toggleable modal
$new_modal = '
    <!-- Add Client Modal -->
    <div id="addClientModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h3>Add New Record</h3>
                <button class="modal-close" onclick="closeAddClientModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addClientForm" onsubmit="saveNewClient(event)">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label">Type <span class="required">*</span></label>
                        <select id="newClientType" class="form-input" onchange="toggleClientFields()">
                            <option value="Client">Client</option>
                            <option value="Student">Student</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" id="nameLabel">Client Name <span class="required">*</span></label>
                        <input type="text" id="newClientName" class="form-input" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" id="newClientPhone" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address (Optional)</label>
                            <input type="email" id="newClientEmail" class="form-input">
                        </div>
                    </div>

                    <!-- Client Specific Fields -->
                    <div id="clientSpecificFields">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">GST Number (Optional)</label>
                            <input type="text" id="newClientGst" class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Billing Address</label>
                            <textarea id="newClientAddress" class="form-input" rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Student Specific Fields -->
                    <div id="studentSpecificFields" style="display: none;">
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label class="form-label">College Name</label>
                            <input type="text" id="newClientCollege" class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">Department</label>
                            <input type="text" id="newClientDepartment" class="form-input">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="width: 100%;">Save Record</button>
                </form>
            </div>
        </div>
    </div>
';

// Add it before the final </body> tag
$content = preg_replace('/<\/body>(?!.*<\/body>)/s', $new_modal . "\n</body>", $content);

file_put_contents('index.php', $content);
echo "Cleaned up and updated index.php with new modal.";
