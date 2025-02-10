<?php
if (!defined('ABSPATH')) {
    exit;
}

class WP_Shortlink_Admin {
    private $table;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submission']);
        require_once WP_SHORTLINKS_PATH . 'includes/class-shortlink-list-table.php';
    }

    /**
	 * Adds the admin menu for the WP-Shortlink-Manager plugin.
	 *
	 * This function adds a submenu page under the Tools menu in the WordPress admin.
	 * The submenu page is used to manage shortlinks.
	 *
	 * @since 1.0.0
	 */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('Shortlinks', 'wp-shortlinks'),
            __('Shortlinks', 'wp-shortlinks'),
            'manage_options',
            'wp-shortlinks',
            [$this, 'render_admin_page']
        );
    }

    /**
	 * Handles the form submission for the WP-Shortlink-Manager plugin.
	 *
	 * This function is responsible for handling the form submission for the
	 * WP-Shortlink-Manager plugin. It checks for the presence of the form
	 * submission, validates the form data, and saves the data to the database.
	 *
	 * @since 1.0.0
	 */
    public function handle_form_submission() {
        if (isset($_POST['submit_shortlink'])) {
            check_admin_referer('wp_shortlinks_nonce_action', 'wp_shortlinks_nonce');
            
            $original_url = esc_url_raw($_POST['original_url']);
            $short_code = sanitize_text_field($_POST['short_code']);
            
            if (!empty($original_url)) {
                WP_Shortlink::create_shortlink($original_url, $short_code);
            }

            wp_redirect(admin_url('tools.php?page=wp-shortlinks'));
            exit;
        }
    }

   /**
	 * Renders the admin page for the WP-Shortlink-Manager plugin.
	 *
	 * This function is responsible for rendering the admin page for the
	 * WP-Shortlink-Manager plugin. It displays the form for creating a new
	 * shortlink and the list of existing shortlinks.
	 *
	 * @since 1.0.0
	 */
    public function render_admin_page() {
        $this->table = new WP_Shortlink_List_Table();
        $this->table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php _e('Shortlinks Manager', 'wp-shortlinks'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('wp_shortlinks_nonce_action', 'wp_shortlinks_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="original_url"><?php _e('Original URL', 'wp-shortlinks'); ?></label></th>
                        <td><input type="url" name="original_url" id="original_url" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="short_code"><?php _e('Custom Short Code (Optional)', 'wp-shortlinks'); ?></label></th>
                        <td><input type="text" name="short_code" id="short_code" class="regular-text"></td>
                    </tr>
                </table>
                <p><input type="submit" name="submit_shortlink" class="button button-primary" value="<?php _e('Create Shortlink', 'wp-shortlinks'); ?>"></p>
            </form>

            <hr>

            <form method="post">
                <?php $this->table->display(); ?>
            </form>
        </div>
        <?php
    }
}
