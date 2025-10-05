<?php
if (!defined('ABSPATH')) exit;

/**
 * Añadir menú en el admin
 */
function seo_locker_admin_menu() {
    add_menu_page(
        'SEO Locker Leads',              // Título de la página
        'SEO Locker',                    // Título en menú
        'manage_options',                // Capacidad necesaria
        'seo-locker',                    // Slug del menú
        'seo_locker_admin_page',         // Callback que muestra la página
        'dashicons-lock',                // Icono del menú
        25                               // Posición en el menú
    );
}
add_action('admin_menu', 'seo_locker_admin_menu');

/**
 * Contenido de la página de administración
 */
function seo_locker_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';

    // Mensajes de éxito
    if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
        echo '<div class="notice notice-success is-dismissible"><p>Lead eliminado correctamente.</p></div>';
    }
    if (isset($_GET['exported']) && $_GET['exported'] == 1) {
        echo '<div class="notice notice-success is-dismissible"><p>Leads exportados correctamente.</p></div>';
    }

    echo '<div class="wrap">';
    echo '<h1>Leads capturados</h1>';

    // --- Botón de exportar CSV con POST ---
    echo '<form method="POST" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-bottom:20px;">';
    echo '<input type="hidden" name="action" value="seocontentlocker_export_csv">';
    wp_nonce_field('seocontentlocker_export');
    echo '<button type="submit" class="button button-primary">Exportar a CSV</button>';
    echo '</form>';

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

 if ($results) {
    echo '<table class="widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Post Slug</th>
                <th>Fecha</th>
                <th>IP</th>
                <th>País</th>
                <th>Status</th>
                <th>Acciones</th>
            </tr>
          </thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->post_slug) . '</td>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '<td>' . esc_html($row->ip) . '</td>';
        echo '<td>' . esc_html($row->country) . '</td>';
        echo '<td>' . esc_html($row->status) . '</td>';
        echo '<td>';

        // --- Botón eliminar con POST ---
        echo '<form method="POST" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline">';
        echo '<input type="hidden" name="action" value="seocontentlocker_delete_lead">';
        echo '<input type="hidden" name="id" value="' . esc_attr($row->id) . '">';
        wp_nonce_field('seocontentlocker_delete_' . $row->id);
        echo '<button type="submit" class="button button-secondary" onclick="return confirm(\'¿Estás seguro de eliminar este lead?\')">Eliminar</button>';
        echo '</form>';

        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p>No hay leads capturados aún.</p>';
}


    echo '</div>';
}
