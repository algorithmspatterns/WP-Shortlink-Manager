<?php
if (!defined('ABSPATH')) {
  exit;
}

/**
 * Checks if the WP_List_Table class exists, and if not, includes the class-wp-list-table.php file.
 *
 * This ensures that the WP_List_Table class is available for use in the plugin.
 *
 * @package WP-Shortlink-Manager
 */
if (!class_exists('WP_List_Table')) {
  require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class WP_Shortlink_List_Table
 *
 * This class extends the WP_List_Table class to manage and display a list of shortlinks
 * in the WordPress admin area.
 *
 * @package WP-Shortlink-Manager
 */
class WP_Shortlink_List_Table extends WP_List_Table
{
  public function __construct()
  {
    parent::__construct([
      'singular' => __('Shortlink', 'wp-shortlinks'),
      'plural'   => __('Shortlinks', 'wp-shortlinks'),
      'ajax'     => false,
    ]);
  }

  /**
   * Retrieves the list of columns for the shortlink list table.
   *
   * @return array Associative array of column IDs and their titles.
   */
  public function get_columns()
  {
    return [
      'cb'           => '<input type="checkbox" />',
      'short_code'   => __('Short Code', 'wp-shortlinks'),
      'original_url' => __('Original URL', 'wp-shortlinks'),
      'click_count'  => __('Clicks', 'wp-shortlinks'),
      'created_at'   => __('Created At', 'wp-shortlinks')
    ];
  }

  /**
   * Generates the checkbox column content for the list table.
   *
   * @param array $item An array of data for the current item.
   * @return string The HTML content for the checkbox column.
   */
  protected function column_cb($item)
  {
    return sprintf(
      '<input type="checkbox" name="shortlink_ids[]" value="%s" class="cb-select" />',
      esc_attr($item['id'])
    );
  }



  /**
   * Retrieves the sortable columns for the shortlink list table.
   *
   * This function returns an array of columns that can be sorted by the user.
   *
   * @return array An associative array of sortable columns.
   */
  public function get_sortable_columns()
  {
    return [
      'short_code'   => ['short_code', true],  // Сортировка по коду ссылки
      'original_url' => ['original_url', true], // Сортировка по оригинальному URL
      'click_count'  => ['click_count', true],  // Сортировка по количеству кликов
      'created_at'   => ['created_at', true]    // Сортировка по дате
    ];
  }

  /**
	 * Retrieves an array of bulk actions available for the list table.
	 *
	 * This method is used to define the bulk actions that can be performed
	 * on the items listed in the table. Bulk actions are actions that can
	 * be applied to multiple items at once.
	 *
	 * @return array An associative array of bulk actions, 
   * where the key is the action name 
   * and the value is the label to be displayed in the bulk actions dropdown.
	 */
  protected function get_bulk_actions()
  {
    return [
      'delete' => __('Delete', 'wp-shortlinks')
    ];
  }

  /**
	 * Processes bulk actions for the shortlink list table.
	 *
	 * This function handles the bulk actions that are performed on the shortlink list table.
	 * It checks the current action and performs the corresponding bulk action.
	 *
	 * @since 1.0.0
	 */
  public function process_bulk_action()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shortlinks';

    if ($this->current_action() === 'delete' && !empty($_POST['shortlink_ids'])) {
      $ids = array_map('intval', $_POST['shortlink_ids']);
      $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));

      // Логирование для отладки (можно удалить)
      error_log("Deleting shortlink IDs: " . implode(',', $ids));

      // Удаление записей
      $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE ID IN ($ids_placeholder)", ...$ids));
    }
  }

  /**
	 * Prepares the list of items for displaying.
	 *
	 * This function is used to prepare the data for the list table.
	 * It handles pagination, sorting, and any other necessary data manipulation.
	 *
	 * @since 1.0.0
	 */
  public function prepare_items()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . 'shortlinks';

    $per_page = $this->get_per_page();
    $current_page = $this->get_pagenum();
    $offset = ($current_page - 1) * $per_page;

    $this->process_bulk_action();

    // Получаем общее количество записей
    $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // Проверяем, есть ли записи
    if ($total_items > 0) {
      // Разрешенные поля для сортировки (чтобы избежать SQL-инъекций)
      $sortable_columns = ['short_code', 'original_url', 'click_count', 'created_at'];

      // Получаем параметры сортировки из GET-запроса
      $orderby = (!empty($_GET['orderby']) && in_array($_GET['orderby'], $sortable_columns)) ? $_GET['orderby'] : 'created_at';
      $order   = (!empty($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc'])) ? strtoupper($_GET['order']) : 'DESC';

      // Формируем SQL-запрос
      $query = $wpdb->prepare(
        "SELECT id, short_code, original_url, click_count, created_at FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d",
        $per_page,
        $offset
      );

      // Выполняем запрос
      $this->items = $wpdb->get_results($query, ARRAY_A);
    } else {
      $this->items = [];
    }

    // Логируем SQL-запрос и загруженные данные
    error_log("SQL Query: " . ($query ?? "No query executed"));
    error_log("Loaded shortlinks: " . print_r($this->items, true));

    // Устанавливаем параметры пагинации, проверяя деление на ноль
    $this->set_pagination_args([
      'total_items' => $total_items,
      'per_page'    => ($per_page > 0) ? $per_page : 10, // Устанавливаем 10 по умолчанию
      'total_pages' => ($per_page > 0) ? ceil($total_items / $per_page) : 1
    ]);
  }

  /**
	 * Displays the rows in the shortlink list table.
	 *
	 * This function is responsible for rendering the rows of data in the shortlink list table.
	 *
	 * @since 1.0.0
	 */
  public function display_rows()
  {
    foreach ($this->items as $item) {
      echo '<tr>';
      foreach ($this->get_columns() as $column_name => $column_display_name) {
        echo '<td>';
        if ($column_name === 'cb') {
          echo $this->column_cb($item); // Вызываем column_cb() для чекбоксов
        } else {
          echo $this->column_default($item, $column_name);
        }
        echo '</td>';
      }
      echo '</tr>';
    }
  }

  /**
	 * Default column rendering.
	 *
	 * This method is used to render the content for a specific column in the list table.
	 *
	 * @param array  $item        The current item in the list table.
	 * @param string $column_name The name of the column to display.
	 *
	 * @return string The content to display for the column.
	 */
  public function column_default($item, $column_name)
  {
    switch ($column_name) {
      case 'short_code':
        return sprintf('<a href="%s" target="_blank">%s</a>', esc_url(home_url($item['short_code'])), esc_html($item['short_code']));
      case 'original_url':
        return sprintf('<a href="%s" target="_blank">%s</a>', esc_url($item['original_url']), esc_html($item['original_url']));
      case 'click_count':
        return intval($item['click_count']);
      case 'created_at':
        return esc_html($item['created_at']);
      default:
        return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
    }
  }

  /**
	 * Displays the shortlink list table.
	 *
	 * This function is responsible for rendering the table that lists all the shortlinks.
	 *
	 * @since 1.0.0
	 */
  public function display()
  {
    $this->process_bulk_action();
?>
    <form method="post">
      <input type="hidden" name="page" value="wp-shortlinks">
      <?php $this->extra_tablenav('top'); ?>
      <table class="wp-list-table widefat fixed striped table-view-list shortlinks">
        <thead>
          <?php $this->print_column_headers(); ?>
        </thead>
        <tbody id="the-list">
          <?php $this->display_rows_or_placeholder(); ?>
        </tbody>
        <tfoot>
          <?php $this->print_column_headers(false); ?>
        </tfoot>
      </table>
      <div class="tablenav bottom">
        <?php $this->bulk_actions('bottom'); ?>
        <?php $this->pagination('bottom'); ?>
      </div>
    </form>
    <?php
  }

  /**
	 * Displays the rows or a placeholder message if no rows are available.
	 *
	 * This function is responsible for rendering the rows of data in the table
	 * or displaying a placeholder message when there are no rows to display.
	 *
	 * @since 1.0.0
	 */
  public function display_rows_or_placeholder()
  {
    if (!empty($this->items)) {
      $this->display_rows();
    } else {
      echo '<tr class="no-items"><td class="colspanchange" colspan="' . count($this->get_columns()) . '">' . __('No items found.', 'wp-shortlinks') . '</td></tr>';
    }
  }

  /**
	 * Retrieves the number of items to be displayed per page.
	 *
	 * This function is used to determine the number of shortlinks to display
	 * on each page of the list table.
	 *
	 * @return int Number of items per page.
	 */
  public function get_per_page()
  {
    $default = 10; // Значение по умолчанию
    $per_page = get_user_meta(get_current_user_id(), 'shortlinks_per_page', true);
    return ($per_page > 0) ? (int) $per_page : $default;
  }

  /**
	 * Prints the column headers for the shortlink list table.
	 *
	 * @param bool $with_id Optional. Whether to include an ID attribute in the column headers. Default true.
	 */
  public function print_column_headers($with_id = true)
  {
    $columns = $this->get_columns();
    $hidden = get_hidden_columns($this->screen);
    $sortable = $this->get_sortable_columns();

    foreach ($columns as $column_key => $column_display_name) {
      $class = "manage-column column-$column_key";
      $style = in_array($column_key, $hidden) ? 'display: none;' : '';

      $orderby_param = $_GET['orderby'] ?? '';
      $order_param   = $_GET['order'] ?? 'desc';
      $next_order    = ($order_param === 'asc') ? 'desc' : 'asc';

      if (isset($sortable[$column_key])) {
        // $class .= ($orderby_param === $column_key) ? ' sorted ' . $order_param : '';
        $class .= ($orderby_param === $column_key) ? " sorted $order_param" : " sortable";

        $column_display_name = sprintf(
          '<a href="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
          esc_url(add_query_arg(['orderby' => $column_key, 'order' => $next_order])),
          esc_html($column_display_name)
        );
      }

      echo "<th scope='col' class='$class' style='$style'>" . $column_display_name . "</th>";
    }
  }

  /**
	 * Outputs extra table navigation markup.
	 *
	 * This function is used to add extra navigation controls to the table.
	 *
	 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
	 */
  public function extra_tablenav($which)
  {
    if ($which === 'top') {
      $per_page = $this->get_per_page(); // Получаем текущее значение
    ?>
      <div class="alignleft actions">
        <label for="shortlinks_per_page"><?php _e('Show per page:', 'wp-shortlinks'); ?></label>
        <select name="shortlinks_per_page" id="shortlinks_per_page">
          <?php
          $options = [5, 10, 20, 50, 100]; // Возможные значения
          foreach ($options as $option) {
            printf(
              '<option value="%d" %s>%d</option>',
              $option,
              selected($per_page, $option, false),
              $option
            );
          }
          ?>
        </select>
        <input type="submit" name="filter_action" id="post-query-submit" class="button" value="<?php _e('Apply', 'wp-shortlinks'); ?>">
      </div>
<?php
    }
  }
}
