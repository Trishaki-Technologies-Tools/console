<?php
$page_title = "Vouchers";
$current_page = "voucher";
require_once 'includes/header.php';
require_once 'includes/sidebar.php';
require_once 'includes/topbar.php';
?>

<div class="page-content" id="voucher-page">
    <div class="section-header">
        <div>
            <h2>Payment Vouchers</h2>
            <p class="company-subtitle">Manage payout vouchers, payees and printing formats</p>
        </div>
        <button class="btn-primary" onclick="showAddVoucherModal()">+ Generate New Voucher</button>
    </div>

    <div class="stats-row-small" style="margin-top: 15px; margin-bottom: 25px;">
        <div class="stat-card-small">
            <div class="stat-icon-small icon-blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
            </div>
            <div class="stat-info-small">
                <div class="stat-value-small" id="total-voucher-count">0</div>
                <div class="stat-label-small">Total Vouchers</div>
            </div>
        </div>
        <div class="stat-card-small">
            <div class="stat-icon-small icon-green">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
                    <circle cx="12" cy="12" r="3"></circle>
                    <line x1="6" y1="12" x2="6.01" y2="12"></line>
                    <line x1="18" y1="12" x2="18.01" y2="12"></line>
                </svg>
            </div>
            <div class="stat-info-small">
                <div class="stat-value-small" id="total-voucher-amount">₹0</div>
                <div class="stat-label-small">Total Disbursed</div>
            </div>
        </div>
    </div>

    <div class="records-section">
        <div class="table-responsive" id="voucher-list">
            <p style="padding: 40px; text-align: center; color: #64748b;">Loading vouchers...</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof loadVouchers === 'function') {
        loadVouchers();
    }
});
</script>

<?php
require_once 'includes/modals.php';
require_once 'includes/footer.php';
?>