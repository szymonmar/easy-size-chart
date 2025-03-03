<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete custom product fields
delete_post_meta_by_key('_easy_size_chart_enabled');
delete_post_meta_by_key('_easy_size_chart_image_enabled');
delete_post_meta_by_key('_easy_size_chart_tab_title');
delete_post_meta_by_key('_easy_size_chart_unspecified_text');
delete_post_meta_by_key('_easy_size_chart_tablepress_shortcode');
delete_post_meta_by_key('_easy_size_chart_image_path');
delete_post_meta_by_key('_easy_size_chart_row_count');
delete_post_meta_by_key('_easy_size_chart_column_count');
delete_post_meta_by_key('_easy_size_chart_data');
delete_post_meta_by_key('_easy_size_chart_shortcode_enabled');

// Delete plugin files
function delete_plugin_directory($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $dir . DIRECTORY_SEPARATOR . $file;
        is_dir($filePath) ? delete_plugin_directory($filePath) : unlink($filePath);
    }
    rmdir($dir);
}

$upload_dir = wp_upload_dir();
$plugin_upload_path = $upload_dir['basedir'] . '/easy-size-chart/';
delete_plugin_directory($plugin_upload_path);