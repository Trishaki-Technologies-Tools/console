<?php
$content = file_get_contents('index.php');

$target1 = '<select id="nonGstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                </select>';

$rep1 = '<select id="nonGstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                </select>';

$content = str_replace($target1, $rep1, $content);

$target2 = '<select id="gstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                </select>';

$rep2 = '<select id="gstPaymentMode" class="form-select">
                                    <option value="">Select Mode</option>
                                </select>';

$content = str_replace($target2, $rep2, $content);

file_put_contents('index.php', $content);
echo "Fixed hardcoded options in index.php";
