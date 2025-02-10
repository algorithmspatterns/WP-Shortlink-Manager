<?php
if (!defined('ABSPATH')) {
    exit;
}

class WP_Shortlink_Security {
    public function __construct() {
        add_action('admin_init', [$this, 'check_permissions']);
    }

    public function check_permissions() {
        if (is_admin() && !current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-shortlinks'));
        }
    }

    public static function verify_nonce($nonce_name, $action) {
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $action)) {
            wp_die(__('Security check failed.', 'wp-shortlinks'));
        }
    }
}

new WP_Shortlink_Security();
