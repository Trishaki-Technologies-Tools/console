<?php
$content = file_get_contents('index.php');
// The file is UTF-8 encoded mojibake.
// Convert from UTF-8 to Windows-1252. This gives the raw bytes.
// Then those raw bytes are actually valid UTF-8!
$raw_bytes = mb_convert_encoding($content, 'Windows-1252', 'UTF-8');
file_put_contents('index_test2.php', $raw_bytes);
echo "Done.";
