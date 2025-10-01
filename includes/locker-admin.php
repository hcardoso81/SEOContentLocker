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

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    echo '<div class="wrap">';
    echo '<h1>Leads capturados</h1>';

    if ($results) {
        echo '<table class="widefat fixed striped">';
        echo '<thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Post Slug</th>
                    <th>Fecha</th>
                </tr>
              </thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->id) . '</td>';
            echo '<td>' . esc_html($row->email) . '</td>';
            echo '<td>' . esc_html($row->post_slug) . '</td>';
            echo '<td>' . esc_html($row->created_at) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No hay leads capturados aún.</p>';
    }

    echo '</div>';
}
