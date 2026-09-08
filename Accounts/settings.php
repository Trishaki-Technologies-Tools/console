<?php
$page_title = "Settings";
$current_page = "settings";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

                <div class="page-content" id="settings-page">
                    <div class="section-header">
                        <div>
                            <h2>Global Settings</h2>
                            <p class="company-subtitle">Configure settings and payment methods</p>
                        </div>
                    </div>

                    <div class="records-section" style="max-width: 800px;">
                        <h3
                            style="color: #fff; margin-bottom: 20px; border-bottom: 1px solid var(--border-light); padding-bottom: 10px;">
                            Payment Settings</h3>
                        <div class="form-group"
                            style="display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 12px; border: 1px solid var(--border-light); margin-bottom: 20px;">
                            <div>
                                <label class="form-label" style="margin-bottom: 0;">Payment Methods Management</label>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">Add, remove, and
                                    manage custom payment methods used across the system.</p>
                            </div>
                            <button type="button" class="btn-secondary" onclick="openPaymentModesModal()">💳 Manage
                                Payment Methods</button>
                        </div>
                    </div>
                </div>
            
        </main>
    

    <!-- Category Management Modal -->



<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>