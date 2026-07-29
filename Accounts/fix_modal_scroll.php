<?php
$content = file_get_contents('index.php');

$old_table = '<div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>';

$new_table = '<style>
                    .hidden-scrollbar::-webkit-scrollbar { display: none; }
                    .hidden-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                </style>
                <div class="table-responsive hidden-scrollbar" style="max-height: 220px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead style="position: sticky; top: 0; z-index: 1;">';

$content = str_replace($old_table, $new_table, $content);
file_put_contents('index.php', $content);

echo "Updated Manage Modal scroll styling.";
