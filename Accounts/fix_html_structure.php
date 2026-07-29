<?php
$content = file_get_contents('index.php');
$test_content = file_get_contents('index_test.php');

// Find the original block from index_test.php
$start = strpos($test_content, '<div class="filter-row"');
// We want to extract specifically the one inside invoices-page
$start = strpos($test_content, '<div class="filter-row"', strpos($test_content, 'id="invoices-page"'));
$end = strpos($test_content, '<input type="file" id="csv-file-input"');
$original_block = substr($test_content, $start, $end - $start);

// Find the broken block in index.php
$broken_start = strpos($content, '<div class="filter-row"', strpos($content, 'id="invoices-page"'));
$broken_end = strpos($content, '<input type="file" id="csv-file-input"');
$broken_block = substr($content, $broken_start, $broken_end - $broken_start);

// Replace it
$content = str_replace($broken_block, $original_block, $content);

file_put_contents('index.php', $content);
echo "HTML structure fixed.";
