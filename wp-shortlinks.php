<?php
/**
 * Plugin Name: WP Shortlinks
 * Plugin URI: https://example.com/wp-shortlinks
 * Description: Плагин для управления сокращением ссылок в WordPress.
 * Version: 1.0.0
 * Author: Konstantin Kriachko
 * Author URI: https://example.com
 * License: GPLv2 or later
 * Text Domain: wp-shortlinks
 */

// Запрет прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант плагина
global $wpdb;
define('WP_SHORTLINKS_VERSION', '1.0.0');
define('WP_SHORTLINKS_PATH', plugin_dir_path(__FILE__));
define('WP_SHORTLINKS_URL', plugin_dir_url(__FILE__));
define('WP_SHORTLINKS_TABLE', $wpdb->prefix . 'shortlinks');

// Подключение необходимых файлов
require_once WP_SHORTLINKS_PATH . 'includes/class-shortlink.php';
require_once WP_SHORTLINKS_PATH . 'includes/class-admin.php';
require_once WP_SHORTLINKS_PATH . 'includes/class-redirect.php';
require_once WP_SHORTLINKS_PATH . 'includes/class-security.php';

// Активизация плагина
function wp_shortlinks_activate() {
    require_once WP_SHORTLINKS_PATH . 'includes/class-shortlink.php';
    WP_Shortlink::install();
}
register_activation_hook(__FILE__, 'wp_shortlinks_activate');

// Деактивация плагина
function wp_shortlinks_deactivate() {
    // Можно добавить дополнительные действия при деактивации
}
register_deactivation_hook(__FILE__, 'wp_shortlinks_deactivate');

function wp_shortlink_admin_scripts($hook) {
  // Проверяем, загружена ли нужная страница
  if (isset($_GET['page']) && $_GET['page'] === 'wp-shortlinks') {
      wp_enqueue_script(
          'shortlink-admin-js',
          plugin_dir_url(__FILE__) . 'assets/shortlink-admin.js',
          [],
          '1.0',
          true
      );
  }
}
add_action('admin_enqueue_scripts', 'wp_shortlink_admin_scripts');

function wp_shortlink_redirect_on_per_page_change() {
  if (isset($_POST['shortlinks_per_page']) && is_numeric($_POST['shortlinks_per_page'])) {
      // Если выполняется массовое удаление, НЕ перенаправляем
      if (!empty($_POST['action']) && $_POST['action'] === 'delete') {
          return;
      }

      update_user_meta(get_current_user_id(), 'shortlinks_per_page', (int) $_POST['shortlinks_per_page']);

      // Перенаправляем на первую страницу (но не при удалении)
      wp_redirect(add_query_arg(['paged' => 1]));
      exit;
  }
}
add_action('admin_init', 'wp_shortlink_redirect_on_per_page_change');

function wp_shortlink_load_admin_styles() {
  echo '<link rel="stylesheet" type="text/css" href="' . esc_url(admin_url('css/list-tables.css')) . '">';
}
add_action('admin_head', 'wp_shortlink_load_admin_styles');

/**
 * Enqueues the admin styles for the WP Shortlink Manager plugin.
 *
 * This function hooks into the WordPress admin_enqueue_scripts action to add custom
 * styles for the WP Shortlink Manager plugin's admin pages.
 *
 * @param string $hook The current admin page.
 */
function wp_shortlink_admin_styles( $hook ) {
	// Проверяем, загружена ли нужная страница плагина
	if ( ! empty( $_GET['page'] ) && $_GET['page'] === 'wp-shortlinks' ) {
		wp_enqueue_style(
			'shortlink-admin-css',
			plugin_dir_url( __FILE__ ) . 'assets/admin-style.css',
			array(),
			'1.0'
		);
	}
}
add_action( 'admin_enqueue_scripts', 'wp_shortlink_admin_styles' );

function wp_shortlink_force_admin_css() {
  echo '<style>
      .manage-column.sorted .sorting-indicator::after {
          content: "▲"; /* Или ▼ */
          display: inline-block;
          font-size: 14px;
          margin-left: 5px;
      }
      .manage-column.sorted.desc .sorting-indicator::after {
          content: "▼";
      }
  </style>';
}
add_action('admin_head', 'wp_shortlink_force_admin_css');


// Инициализация плагина
function wp_shortlinks_init() {
    if (is_admin()) {
        new WP_Shortlink_Admin();
    }
    new WP_Shortlink_Redirect();
    new WP_Shortlink_Security();
}
add_action('init', 'wp_shortlinks_init');
