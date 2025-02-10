<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WP_Shortlink_Redirect
 *
 * Handles the redirection of shortlinks to their original URLs.
 */
class WP_Shortlink_Redirect {
    public function __construct() {
        add_action('init', [$this, 'handle_redirect']);
    }

  /**
	 * Handles the redirection of shortlinks.
	 *
	 * This method checks the request URI, retrieves the original URL from the database,
	 * increments the click count, and performs a 301 redirect to the original URL.
	 */
    public function handle_redirect() {
        global $wpdb;
        
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        if (empty($request_uri)) {
            return;
        }
        
        $table_name = $wpdb->prefix . 'shortlinks';
        $original_url = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT original_url FROM $table_name WHERE short_code = %s",
                sanitize_text_field($request_uri)
            )
        );
        
        if ($original_url) {
            // Увеличение счетчика кликов
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table_name SET click_count = click_count + 1 WHERE short_code = %s",
                    sanitize_text_field($request_uri)
                )
            );
            
            wp_redirect($original_url, 301);
            exit;
        }
    }
}

new WP_Shortlink_Redirect();
