<?php
$content = file_get_contents('index.php');

// My previous powershell script `Set-Content -Encoding UTF8` converted an ISO-8859-1 string to UTF-8.
// And then I mistakenly used `[System.Text.Encoding]::GetEncoding("ISO-8859-1")` and wrote it back as UTF-8.
// Let's just do a simple mapping: 
// 1. read UTF-8 string
// 2. convert UTF-8 back to ISO-8859-1 (this gives us the original raw bytes of the file before PowerShell touched it!)
// 3. those raw bytes ARE the correct UTF-8 string!

$restored = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
file_put_contents('index.php', $restored);
echo "Restored!";
