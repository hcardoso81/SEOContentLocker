<?php
namespace SeoContentLocker\Admin;

if (!defined('ABSPATH')) exit;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SameIpTable extends \WP_List_Table
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

    public function column_email($item)
    {
        $delete_url = wp_nonce_url(
            admin_url("admin-post.php?action=seocontentlocker_delete_same_ip&id={$item->id}"),
            'delete_same_ip_' . $item->id
        );

        $actions = [
            'delete' => sprintf(
                '<a href="%s" style="color:red;" onclick="return confirm(\'¿Seguro que quieres eliminar este registro?\')">Eliminar</a>',
                $delete_url
            ),
        ];

        return sprintf('%1$s %2$s', esc_html($item->email), $this->row_actions($actions));
    }

    /**
     * Columna primaria
     */
    public function get_primary_column_name()
    {
        return 'email';
    }

    public function get_bulk_actions()
    {
        return [
            'bulk_delete_same_ip' => 'Eliminar seleccionados',
        ];
    }

    /**
     * Preparar items para la tabla
     */
    public function prepare_items()
    {
        $repository = new \SeoContentLocker\Repositories\SameIpRepository();

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = [$columns, $hidden, $sortable];

        $per_page    = 20;
        $current_page = $this->get_pagenum();
        $offset      = ($current_page - 1) * $per_page;

        // Total de items
        $total_items = $repository->count();

        $this->items = $repository->paginate($per_page, $offset);

        // Paginación
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
    }
}
