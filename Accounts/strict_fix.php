<?php
$content = file_get_contents('js/app.js');

// 1. Add class to table to make it look premium
$content = str_replace('let html = `<table>', 'let html = `<table class="records-table" style="width:100%; border-collapse: separate; border-spacing: 0; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-top:20px;">', $content);

// 2. Make sure the headers are replaced properly again just in case
$pattern_thead = '/<tr>\s*<th>ID<\/th>\s*<th>Client Name<\/th>\s*<th>Phone<\/th>\s*<th>Email<\/th>\s*<th>GST Number<\/th>\s*<th>Invoices<\/th>\s*<th>Last Invoice Date<\/th>\s*<th>Actions<\/th>\s*<\/tr>/is';
$new_thead = '<tr>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">S.No</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Name</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Type</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Phone</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Email</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">GST Number</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Invoices</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Last Invoice Date</th>
                        <th style="background:#f8fafc; padding:15px; font-weight:700; color:#334155; font-size:12px; text-transform:uppercase; border-bottom:2px solid #e2e8f0;">Actions</th>
                    </tr>';
$content = preg_replace($pattern_thead, $new_thead, $content);

file_put_contents('js/app.js', $content);

// 3. Force cache bust strictly in index.php
$index = file_get_contents('index.php');
$index = preg_replace('/src="js\/app\.js[^"]*"/', 'src="js/app.js?v=' . rand(10000, 99999) . '_' . time() . '"', $index);
file_put_contents('index.php', $index);

echo "Visuals updated and strict cache busted.";
