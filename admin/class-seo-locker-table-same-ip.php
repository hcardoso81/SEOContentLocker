<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SEO_Locker_Table_Same_IP extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'lead_same_ip',
            'plural'   => 'leads_same_ip',
            'ajax'     => false
        ]);
    }

    /**
     * Columnas de la tabla
     */
    public function get_columns()
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'email'      => 'Email',
            'ip'         => 'IP',
            'country'    => 'País',
            'post_slug'  => 'Post',
            'created_at' => 'Creado',
        ];
    }

    /**
     * Columnas ordenables
     */
    public function get_sortable_columns()
    {
        return [
            'email'      => ['email', true],
            'ip'         => ['ip', true],
            'country'    => ['country', true],
            'created_at' => ['created_at', true],
        ];
    }

    /**
     * Checkbox para bulk actions
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="lead_same_ip[]" value="%s" />',
            $item->id
        );
    }

    /**
     * Render default column
     */
    public function column_default($item, $column_name)
    {
        return isset($item->$column_name) && $item->$column_name !== null
            ? esc_html($item->$column_name)
            : '-';
    }

    /**
     * Columna primaria
     */
    public function get_primary_column_name()
    {
        return 'email';
    }

    /**
     * Preparar items para la tabla
     */
    public function prepare_items()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'leads_subscriptions_same_ip';

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page    = 20;
        $current_page = $this->get_pagenum();
        $offset      = ($current_page - 1) * $per_page;

        // Total de items
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        // Obtener items con objetos stdClass
        $this->items = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT $per_page OFFSET $offset"
        );

        // Paginación
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
    }
}
