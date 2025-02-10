<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'shortlinks';

// Удаление таблицы
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Удаление опций (если добавлялись в будущем)
// delete_option('wp_shortlinks_option');
