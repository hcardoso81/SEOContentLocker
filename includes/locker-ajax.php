<?php
if (!defined('ABSPATH')) exit;

/**
 * Guardar leads vía AJAX
 */
function seocontentlocker_save_lead() {
    // --- Verificar nonce ---
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seocontentlocker_nonce')) {
        wp_send_json_error(['message' => __('Invalid request. Please try again.', 'seocontentlocker')]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';

    $email = sanitize_email($_POST['email']);
    $slug  = sanitize_text_field($_POST['slug']);

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address', 'seocontentlocker')]);
    }

    // Verificar si existe
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email)
    );

    if ($exists) {
        wp_send_json_error([
            'message' => __('Your free trial period has already ended.', 'seocontentlocker'),
            'trialExpired' => true
        ]);
    }

    // Insertar lead
    $wpdb->insert(
        $table_name,
        [
            'email'      => $email,
            'created_at' => current_time('mysql', 1), // UTC
            'post_slug'  => $slug,
        ],
        ['%s', '%s', '%s']
    );

    wp_send_json_success([
        'message' => __('You have successfully subscribed to the 40-day free trial!', 'seocontentlocker')
    ]);
}

// Hooks AJAX
add_action('wp_ajax_seocontentlocker_save_lead', 'seocontentlocker_save_lead');
add_action('wp_ajax_nopriv_seocontentlocker_save_lead', 'seocontentlocker_save_lead');

function seocontentlocker_export_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos suficientes.');
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'seocontentlocker_export')) {
        wp_die('Nonce inválido.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

    if (!$results) {
        wp_die('No hay leads para exportar.');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');

    // Encabezados
    fputcsv($output, ['ID', 'Email', 'Post Slug', 'Fecha']);

    foreach ($results as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
add_action('admin_post_seocontentlocker_export_csv', 'seocontentlocker_export_csv');

function seocontentlocker_delete_lead() {
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos suficientes.');
    }
    $id = intval($_GET['id'] ?? 0);
    if (!$id) wp_die('ID inválido.');

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'seocontentlocker_delete_' . $id)) {
        wp_die('Nonce inválido.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';
    $wpdb->delete($table_name, ['id' => $id], ['%d']);

    wp_redirect(admin_url('admin.php?page=seo-locker&deleted=1'));
    exit;
}
add_action('admin_post_seocontentlocker_delete_lead', 'seocontentlocker_delete_lead');


