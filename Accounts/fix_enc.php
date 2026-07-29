<?php
$content = file_get_contents('index.php');
// The string was read as Windows-1252 and saved as UTF-8. 
// utf8_decode converts from UTF-8 back to ISO-8859-1 (which maps close to Windows-1252).
// However, Windows-1252 has some characters (like the Rupee symbol) that ISO-8859-1 doesn't.
// So let's use mb_convert_encoding instead.
$restored = mb_convert_encoding($content, 'Windows-1252', 'UTF-8');
file_put_contents('index_test.php', $restored);
echo "Done.";
