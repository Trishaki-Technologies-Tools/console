<?php
$content = file_get_contents('index.php');

// 1. Restore Non-GST Invoice Header
$bad_non_gst_header = '<style>
                            .client-type-btn { border: none; padding: 8px; border-radius: 6px; font-weight: 500; cursor: pointer; flex: 1; text-align: center; background: transparent; color: #94a3b8; transition: all 0.2s; }
                            .client-type-btn.active { background: #3b82f6; color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                        </style>
                        <div style="display: flex; background: #0f172a; padding: 4px; border: 1px solid #334155; border-radius: 8px; gap: 4px;">
                            <button type="button" class="client-type-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')">Client</button>
                            <button type="button" class="client-type-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')">Student</button>
                        </div>
                <button class="modal-close" onclick="closeNonGstModal()">&times;</button>';

$good_non_gst_header = '<div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px; align-items: center; width: 100%; max-width: 300px;">
                    <button type="button" class="ledger-tab-btn" onclick="switchToGstModal()" style="border: none;">GST Invoice (18%)</button>
                    <button type="button" class="ledger-tab-btn active" style="border: none;">Non-GST Invoice</button>
                </div>
                <button class="modal-close" onclick="closeNonGstModal()">&times;</button>';

$content = str_replace($bad_non_gst_header, $good_non_gst_header, $content);

// 2. Restore GST Invoice Header
$bad_gst_header = '<style>
                            .client-type-btn { border: none; padding: 8px; border-radius: 6px; font-weight: 500; cursor: pointer; flex: 1; text-align: center; background: transparent; color: #94a3b8; transition: all 0.2s; }
                            .client-type-btn.active { background: #3b82f6; color: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
                        </style>
                        <div style="display: flex; background: #0f172a; padding: 4px; border: 1px solid #334155; border-radius: 8px; gap: 4px;">
                            <button type="button" class="client-type-btn active" id="btnTypeClient" onclick="setClientType(\'Client\')">Client</button>
                            <button type="button" class="client-type-btn" id="btnTypeStudent" onclick="setClientType(\'Student\')">Student</button>
                        </div>
                <button class="modal-close" onclick="closeGstModal()">&times;</button>';

$good_gst_header = '<div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px; align-items: center; width: 100%; max-width: 300px;">
                    <button type="button" class="ledger-tab-btn active" style="border: none;">GST Invoice (18%)</button>
                    <button type="button" class="ledger-tab-btn" onclick="switchToNonGstModal()" style="border: none;">Non-GST Invoice</button>
                </div>
                <button class="modal-close" onclick="closeGstModal()">&times;</button>';

$content = str_replace($bad_gst_header, $good_gst_header, $content);

file_put_contents('index.php', $content);
echo "Restored Invoice Headers!";
