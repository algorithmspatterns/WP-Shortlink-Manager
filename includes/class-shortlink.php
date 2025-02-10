<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WP_Shortlink
 *
 * This class handles the creation and management of shortlinks within the WordPress environment.
 *
 * @package WP-Shortlink-Manager
 */
class WP_Shortlink {
    private static $table_name;

  /**
	 * Initialize the Shortlink Manager.
	 *
	 * This static method sets up the necessary hooks and actions for the Shortlink Manager plugin.
	 *
	 * @since 1.0.0
	 */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'shortlinks';
    }

  /**
	 * Handles the installation process for the WP-Shortlink-Manager plugin.
	 *
	 * This method is called when the plugin is activated. It sets up the necessary
	 * database tables and options required for the plugin to function correctly.
	 *
	 * @since 1.0.0
	 */
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

  /**
	 * Creates a shortlink for the given original URL.
	 *
	 * @param string $original_url The original URL to be shortened.
	 * @param string $short_code   Optional. The custom short code for the URL. Default is an empty string.
	 * @return string The generated shortlink.
	 */
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

  /**
	 * Retrieves the original URL associated with a given short code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $short_code The short code for which to retrieve the original URL.
	 * @return string|false The original URL if found, false otherwise.
	 */
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

  /**
	 * Increments the click count for a given short code.
	 *
	 * @param string $short_code The short code for which to increment the click count.
	 */
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

  /**
	 * Deletes a shortlink based on the provided short code.
	 *
	 * @since 1.0.0
	 *
	 * @param string $short_code The short code of the shortlink to delete.
	 * @return void
	 */
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
