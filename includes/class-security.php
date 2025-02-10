<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WP_Shortlink_Security
 *
 * This class handles security-related functionalities for the WP Shortlink Manager plugin.
 *
 * @package    WP_Shortlink_Manager
 * @subpackage WP_Shortlink_Manager/includes
 */
class WP_Shortlink_Security {
    public function __construct() {
        add_action('admin_init', [$this, 'check_permissions']);
    }

  /**
	 * Check user permissions.
	 *
	 * This function verifies if the current user has the necessary permissions
	 * to perform certain actions within the WP-Shortlink-Manager plugin.
	 *
	 * @return void
	 */
    public function check_permissions() {
        if (is_admin() && !current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wp-shortlinks'));
        }
    }
  
  /**
	 * Verify the nonce for a given action.
	 *
	 * This function checks the validity of a nonce for a specified action.
	 *
	 * @param string $nonce_name The name of the nonce to verify.
	 * @param string $action The action associated with the nonce.
	 * @return bool True if the nonce is valid, false otherwise.
	 */
    public static function verify_nonce($nonce_name, $action) {
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $action)) {
            wp_die(__('Security check failed.', 'wp-shortlinks'));
        }
    }
}

new WP_Shortlink_Security();
