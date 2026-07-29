<?php
// Restore from the pristine index_test2.php
$content = file_get_contents('index_test2.php');

// Apply the layout spacing fix
$content = str_replace(
    '<link rel="stylesheet" href="css/style.css?v=2">',
    '<link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    <style>
        /* Fallback Layout Spacing Fixes */
        .stats-row-small { display: flex !important; flex-wrap: wrap !important; gap: 20px !important; margin-bottom: 24px !important; }
        .stat-card-small { flex: 1 1 220px !important; min-width: 220px !important; }
        .dashboard-section { margin-bottom: 35px !important; }
        .records-section { margin-top: 24px !important; margin-bottom: 24px !important; }
    </style>',
    $content
);

file_put_contents('index.php', $content);
echo "Restored from pristine backup and applied CSS fix safely via PHP.";
