<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class SEO_Locker_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'lead',
            'plural'   => 'leads',
            'ajax'     => false
        ]);
    }

    protected function extra_tablenav($which)
    {
        if ($which === 'top') {
            $export_url = wp_nonce_url(
                admin_url('admin-post.php?action=seocontentlocker_export_csv'),
                'seocontentlocker_export_csv'
            );
?>
            <div class="alignleft actions">
                <a href="<?php echo esc_url(
                                add_query_arg([
                                    'action'  => 'seocontentlocker_export_csv',
                                    'orderby' => $_GET['orderby'] ?? 'created_at',
                                    'order'   => $_GET['order'] ?? 'desc',
                                    's'       => $_GET['s'] ?? '',
                                    'paged'   => $_GET['paged'] ?? 1,
                                ], admin_url('admin-post.php'))
                            ); ?>" class="button">
                    Exportar CSV
                </a>
            </div>
<?php
        }
    }

    /**
     * Columnas visibles
     */
    function get_columns()
    {
        return [
            'cb' => '<input type="checkbox" />',
            'email' => 'Email',
            'ip' => 'IP',
            'country' => 'País',
            'post_slug' => 'Slug',
            'created_at' => 'Fecha Alta',
            'expires_at' => 'Expira'
        ];
    }

    /**
     * Columnas ordenables
     */
    public function get_sortable_columns()
    {
        return [
            'email'      => ['email', true],
            'country'    => ['country', true],
            'post_slug' => ['post_slug', true],
            'created_at' => ['created_at', true],
            'expires_at' => ['expires_at', true],
        ];
    }

    /**
     * Checkbox para bulk actions
     */
    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="lead[]" value="%s" />', $item->id);
    }

    /**
     * Default column render
     */
    function column_default($item, $column_name)
    {
        $value = isset($item->$column_name) && $item->$column_name !== null ? $item->$column_name : '-';
        return esc_html($value);
    }

    /**
     * Columnas con acciones inline para email
     */
    public function column_email($item)
    {
        $current_page_params = [
            'page'    => $_GET['page'] ?? SLUG,
            'orderby' => $_GET['orderby'] ?? 'created_at',
            'order'   => $_GET['order'] ?? 'desc',
        ];

        // Agregar búsqueda si existe
        if (!empty($_REQUEST['s'])) {
            $current_page_params['s'] = sanitize_text_field($_REQUEST['s']);
        }

        // Agregar paginación si existe
        if (!empty($_REQUEST['paged'])) {
            $current_page_params['paged'] = intval($_REQUEST['paged']);
        }

        $base_query = http_build_query($current_page_params);

        $actions = [
            'expire' => sprintf(
                '<a href="%s" style="color:#dc2626;">Expirar</a>',
                wp_nonce_url(admin_url("admin-post.php?action=seocontentlocker_expire_lead&id={$item->id}&{$base_query}"), 'expire_' . $item->id)
            ),

            'change_date' => sprintf(
                '<a href="#" onclick="openEditModal(%d, \'%s\', \'%s\', \'%s\'); return false;" style="color:#2563eb;">Cambiar fecha</a>',
                $item->id,
                esc_js(!empty($item->expires_at) && $item->expires_at !== '0000-00-00 00:00:00' ? date('Y-m-d', strtotime($item->expires_at)) : ''),
                esc_js(!empty($item->expires_at) && $item->expires_at !== '0000-00-00 00:00:00' ? date('d/m/Y', strtotime($item->expires_at)) : ''),
                esc_js($item->email)
            ),

            'delete' => sprintf(
                '<a href="%s" style="color:red;" onclick="return confirm(\'¿Seguro que quieres eliminar este lead?\')">Eliminar</a>',
                wp_nonce_url(admin_url("admin-post.php?action=seocontentlocker_delete_lead&id={$item->id}&{$base_query}"), 'delete_' . $item->id)
            ),
        ];

        return sprintf('%1$s %2$s', esc_html($item->email), $this->row_actions($actions));
    }

    public function column_created_at($item)
    {
        if (empty($item->created_at) || $item->created_at === '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = strtotime($item->created_at);
        $formatted = date('d/m/Y', $timestamp);

        return esc_html($formatted);
    }


    public function column_expires_at($item)
    {
        if (empty($item->expires_at) || $item->expires_at === '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = strtotime($item->expires_at);
        $formatted = date('d/m/Y', $timestamp);

        $is_expired = $timestamp < time();

        if ($is_expired) {
            return '<span class="lead-expired" style="color:#dc2626; font-weight:bold;">' . esc_html($formatted) . '</span>';
        }

        return esc_html($formatted);
    }

    /**
     * Definir columna primaria (OBLIGATORIO)
     */
    public function get_primary_column_name()
    {
        return 'email';
    }

    /**
     * Bulk actions
     */
    public function get_bulk_actions()
    {
        return [
            'bulk_delete' => 'Eliminar seleccionados'
        ];
    }

    /**
     * Preparar items
     */

    public function prepare_items()
    {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = !empty($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : null;

        $sortable = $this->get_sortable_columns();
        $columns = $this->get_columns();
        $this->_column_headers = [$columns, [], $sortable];

        $valid_orderby = ['email', 'country', 'created_at', 'expires_at', 'post_slug'];
        $orderby = isset($_GET['orderby']) && in_array($_GET['orderby'], $valid_orderby)
            ? $_GET['orderby']
            : 'created_at';

        $order = (isset($_GET['order']) && strtolower($_GET['order']) === 'asc') ? 'ASC' : 'DESC';

        $offset = ($current_page - 1) * $per_page;

        // 🔥 Ahora todo viene del helper
        $total_items = db_count_leads($search);

        $this->items = db_get_leads(
            $orderby,
            $order,
            $per_page,
            $offset,
            $search
        );

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page
        ]);
    }
}
