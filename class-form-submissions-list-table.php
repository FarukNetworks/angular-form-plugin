<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Form_Submissions_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'submission',
            'plural'   => 'submissions',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'cb'              => '<input type="checkbox" />',
            'submission_date' => 'Date',
            'name'            => 'Name',
            'email'           => 'Email',
            'phone'           => 'Phone',
            'street'          => 'Street',
            'is_holder'       => 'Is Holder'
        ];
    }

    public function get_sortable_columns() {
        return [
            'submission_date' => ['submission_date', true],
            'name'            => ['name', false],
            'email'           => ['email', false],
            'phone'           => ['phone', false],
            'street'          => ['street', false],
            'is_holder'       => ['is_holder', false]
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'client_form_data_custom';

        $per_page = 20;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'submission_date';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'DESC';

        // Ensure $orderby is a valid column name to prevent SQL injection
        $allowed_columns = array_keys($this->get_sortable_columns());
        if (!in_array($orderby, $allowed_columns)) {
            $orderby = 'submission_date';
        }

        // Ensure $order is either ASC or DESC
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        $data = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY {$orderby} {$order}",
            ARRAY_A
        );

        $current_page = $this->get_pagenum();
        $total_items = count($data);

        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'is_holder':
                return $item[$column_name] ? 'Yes' : 'No';
            case 'submission_date':
                return date('Y-m-d H:i:s', strtotime($item[$column_name]));
            default:
                return $item[$column_name];
        }
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="submission[]" value="%s" />',
            $item['id']
        );
    }
}