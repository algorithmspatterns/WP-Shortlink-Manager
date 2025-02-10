<?php
if (!defined('ABSPATH')) {
    exit;
}

class WP_Shortlink {
    private static $table_name;

    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'shortlinks';
    }

    public static function install() {
        global $wpdb;
        self::init();
        
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS " . self::$table_name . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            short_code VARCHAR(50) NOT NULL UNIQUE,
            original_url TEXT NOT NULL,
            click_count BIGINT UNSIGNED DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function create_shortlink($original_url, $short_code = '') {
        global $wpdb;
        self::init();
        
        if (empty($short_code)) {
            $short_code = substr(md5(uniqid()), 0, 6);
        }

        $wpdb->insert(
            self::$table_name,
            [
                'short_code' => sanitize_text_field($short_code),
                'original_url' => esc_url_raw($original_url),
            ],
            ['%s', '%s']
        );

        return $short_code;
    }

    public static function get_original_url($short_code) {
        global $wpdb;
        self::init();
        
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT original_url FROM " . self::$table_name . " WHERE short_code = %s",
                sanitize_text_field($short_code)
            )
        );
    }

    public static function increment_click_count($short_code) {
        global $wpdb;
        self::init();
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE " . self::$table_name . " SET click_count = click_count + 1 WHERE short_code = %s",
                sanitize_text_field($short_code)
            )
        );
    }

    public static function delete_shortlink($short_code) {
        global $wpdb;
        self::init();
        
        return $wpdb->delete(
            self::$table_name,
            ['short_code' => sanitize_text_field($short_code)],
            ['%s']
        );
    }
}

WP_Shortlink::init();
