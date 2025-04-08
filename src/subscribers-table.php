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
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = [];
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
}
