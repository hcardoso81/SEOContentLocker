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

    // Botón de exportar CSV
    $export_nonce = wp_create_nonce('seocontentlocker_export');
    $export_url   = admin_url("admin-post.php?action=seocontentlocker_export_csv&_wpnonce={$export_nonce}");
    echo '<p><a href="' . esc_url($export_url) . '" class="button button-primary">Exportar a CSV</a></p>';

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    if ($results) {
        echo '<table class="widefat fixed striped">';
        echo '<thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Post Slug</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
              </thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            $delete_nonce = wp_create_nonce('seocontentlocker_delete_' . $row->id);
            $delete_url   = admin_url("admin-post.php?action=seocontentlocker_delete_lead&id={$row->id}&_wpnonce={$delete_nonce}");

            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->post_slug) . '</td>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url($delete_url) . '" class="button button-secondary" onclick="return confirm(\'¿Estás seguro de eliminar este lead?\')">Eliminar</a>';
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
