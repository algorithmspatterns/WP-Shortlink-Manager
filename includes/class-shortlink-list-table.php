<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WP_Shortlink_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => __('Shortlink', 'wp-shortlinks'),
            'plural'   => __('Shortlinks', 'wp-shortlinks'),
            'ajax'     => false,
        ]);
    }

    public function get_columns() {
      return [
          'cb'           => '<input type="checkbox" />',
          'short_code'   => __('Short Code', 'wp-shortlinks'),
          'original_url' => __('Original URL', 'wp-shortlinks'),
          'click_count'  => __('Clicks', 'wp-shortlinks'),
          'created_at'   => __('Created At', 'wp-shortlinks')
      ];
  }
  

  protected function column_cb($item) {
    return sprintf(
        '<input type="checkbox" name="shortlink_ids[]" value="%s" class="cb-select" />',
        esc_attr($item['id'])
    );
}

  

public function get_sortable_columns() {
  return [
      'short_code'   => ['short_code', true],  // Сортировка по коду ссылки
      'original_url' => ['original_url', true], // Сортировка по оригинальному URL
      'click_count'  => ['click_count', true],  // Сортировка по количеству кликов
      'created_at'   => ['created_at', true]    // Сортировка по дате
  ];
}


    protected function get_bulk_actions() {
        return [
            'delete' => __('Delete', 'wp-shortlinks')
        ];
    }

    public function process_bulk_action() {
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

    public function prepare_items() {
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

  public function display_rows() {
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



public function column_default($item, $column_name) {
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

public function display() {
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


public function display_rows_or_placeholder() {
  if (!empty($this->items)) {
      $this->display_rows();
  } else {
      echo '<tr class="no-items"><td class="colspanchange" colspan="' . count($this->get_columns()) . '">' . __('No items found.', 'wp-shortlinks') . '</td></tr>';
  }
}

public function get_per_page() {
  $default = 10; // Значение по умолчанию
  $per_page = get_user_meta(get_current_user_id(), 'shortlinks_per_page', true);
  return ($per_page > 0) ? (int) $per_page : $default;
}


public function print_column_headers($with_id = true) {
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

public function extra_tablenav($which) {
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
