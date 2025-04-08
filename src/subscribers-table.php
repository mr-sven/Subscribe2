<?php

declare(strict_types=1);

namespace SMini;

class SubscribersTable extends \WP_List_Table {

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
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->items = [];
    }
}