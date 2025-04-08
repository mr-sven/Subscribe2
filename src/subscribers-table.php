<?php

declare(strict_types=1);

namespace SMini;

class SubscribersTable extends \WP_List_Table
{
    // define $table_data property
    private $table_data;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct([
            'singular' => 'subscriber',
            'plural'   => 'subscribers',
            'ajax'     => false,
        ]);
    }

    public function process_bulk_action()
    {
        $action = $this->current_action();

        if (!in_array($action, ['delete_all'], true)) {
            return;
        }

        // security check!
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-' . $this->_args['plural'])) {
            echo '<div id="message" class="error"><p><strong>' . esc_html__('Error: Nonce verification failed.', SMLD) . '</strong></p></div>';
            return;
        }

        switch ($action) {

            case 'delete_all':
                break;

            default:
                return;
        }
    }

    // Get table data
    private function get_table_data($filter = 'all', $search = '')
    {
        global $wpdb;

        $filter = in_array($filter, ['all', 'active', 'not_active']) ? $filter : 'all';

        if ($filter == 'all') {
            if (!empty($search)) {
                $prepare = $wpdb->prepare("SELECT * from $wpdb->smini WHERE email Like %s", '%' . $search . '%');
            } else {
                $prepare = $wpdb->prepare("SELECT * from $wpdb->smini");
            }
        } else {
            $active = ($filter == 'active') ? 1 : 0;
            if (!empty($search)) {
                $prepare = $wpdb->prepare("SELECT * from $wpdb->smini WHERE email Like %s AND active = %d", '%' . $search . '%', $active);
            } else {
                $prepare = $wpdb->prepare("SELECT * from $wpdb->smini WHERE active = %d", $active);
            }
        }

        return $wpdb->get_results($prepare, ARRAY_A);
    }

    public function get_views()
    {
        $current = (!empty($_REQUEST['filter']) ? $_REQUEST['filter'] : 'all');
        return [
            "all"        => '<a href="' . remove_query_arg('filter') . '" ' . ($current == 'all' ? ' class="current"' : '') . '>' . esc_html__("All", SMLD) . '</a>',
            "active"     => '<a href="' . add_query_arg('filter', 'active') . '" ' . ($current == 'active' ? ' class="current"' : '') . '>' . esc_html__("Active", SMLD) . '</a>',
            "not_active" => '<a href="' . add_query_arg('filter', 'not_active') . '" ' . ($current == 'not_active' ? ' class="current"' : '') . '>' . esc_html__("Not active", SMLD) . '</a>'
        ];
    }

    // Define table columns
    public function get_columns()
    {
        return [
            'cb'        => '<input type="checkbox" />',
            'email'     => __('EMail', SMLD),
            'active'    => __('Active', SMLD),
            'ts'        => __('Date registered', SMLD),
            'active_ts' => __('Date activated', SMLD)
        ];
    }

    // Bind table with columns, data and all
    public function prepare_items()
    {
        $this->process_bulk_action();

        $filter = (isset($_REQUEST['filter']) ? $_REQUEST['filter'] : 'all');

        if (isset($_POST['s'])) {
            $this->table_data = $this->get_table_data($filter, $_POST['s']);
        } else {
            $this->table_data = $this->get_table_data($filter);
        }

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();
        $primary = 'email';
        $this->_column_headers = [$columns, $hidden, $sortable, $primary];
        usort($this->table_data, [&$this, 'usort_reorder']);

        /* pagination */
        $per_page = $this->get_items_per_page('per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items = count($this->table_data);

        $this->table_data = array_slice($this->table_data, (($current_page - 1) * $per_page), $per_page);

        $this->set_pagination_args([
            'total_items' => $total_items, // total number of items
            'per_page'    => $per_page, // items to show on a page
            'total_pages' => ceil($total_items / $per_page) // use ceil to round up
        ]);

        $this->items = $this->table_data;
    }

    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'email':
            case 'active':
            case 'ts':
            case 'active_ts':
            default:
                return $item[$column_name];
        }
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="element[]" value="%s" />', $item['id']);
    }

    // Adding action links to column
    public function column_email($item)
    {
        $actions = [
            'delete' => sprintf('<a href="?page=%s&action=%s&element=%s">' . __('Delete', SMLD) . '</a>', $_REQUEST['page'], 'delete', $item['id'])
        ];
        return sprintf('%1$s %2$s', $item['email'], $this->row_actions($actions));
    }

    public function get_sortable_columns()
    {
        return [
            'email'  => ['email', false],
            'active' => ['active', false]
        ];
    }

    // To show bulk action dropdown
    function get_bulk_actions()
    {
        return [
            'delete_all' => __('Delete', SMLD),
        ];
    }

    // Sorting function
    function usort_reorder($a, $b)
    {
        // If no sort, default to user_login
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'email';

        // If no order, default to asc
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';

        // Determine sort order
        $result = strcmp($a[$orderby], $b[$orderby]);

        // Send final sort direction to usort
        return ($order === 'asc') ? $result : -$result;
    }
}
