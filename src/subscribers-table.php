<?php

declare(strict_types=1);

namespace SMini;

class SubscribersTable extends \WP_List_Table
{
    // define $table_data property
    private $table_data;

    // Get table data
    private function get_table_data() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $wpdb->smini", ARRAY_A);
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
        //data
        $this->table_data = $this->get_table_data();

        $columns = $this->get_columns();
        $sortable = $this->get_sortable_columns();

        $hidden = [];
        $sortable = [];
        $primary  = 'email';
        $this->_column_headers = [$columns, $hidden, $sortable, $primary];
        usort($this->table_data, [&$this, 'usort_reorder']);
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

    public function get_sortable_columns()
    {
        return [
            'email'  => ['email', true],
            'active' => ['active', false]
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
