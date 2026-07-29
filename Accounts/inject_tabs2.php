<?php
$content = file_get_contents('index.php');

// Replace header in Non-GST Modal
$target_nongst = '<h3>Non-GST Invoice Details</h3>';
$rep_nongst = '<div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px; align-items: center;">
                    <button type="button" class="ledger-tab-btn" onclick="switchToGstModal()" style="border: none;">GST Invoice (18%)</button>
                    <button type="button" class="ledger-tab-btn active" style="border: none;">Non-GST Invoice</button>
                </div>';
$content = str_replace($target_nongst, $rep_nongst, $content);

// Replace header in GST Modal
$target_gst = '<h3>GST Invoice Details</h3>';
$rep_gst = '<div class="ledger-tabs" style="display: flex; background: #e5e7eb; padding: 4px; border-radius: 6px; gap: 4px; align-items: center;">
                    <button type="button" class="ledger-tab-btn active" style="border: none;">GST Invoice (18%)</button>
                    <button type="button" class="ledger-tab-btn" onclick="switchToNonGstModal()" style="border: none;">Non-GST Invoice</button>
                </div>';
$content = str_replace($target_gst, $rep_gst, $content);

file_put_contents('index.php', $content);
echo "Headers updated with tab switchers properly this time.";
