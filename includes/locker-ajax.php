<?php
if (!defined('ABSPATH')) exit;

/**
 * Obtener país desde IP usando ipapi.co
 */
function seocontentlocker_get_country_from_ip($ip)
{
    if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
        return 'Unknown';
    }

    // Usar wp_remote_get en lugar de file_get_contents (más seguro en WordPress)
    $response = wp_remote_get("https://ipapi.co/{$ip}/json/", [
        'timeout' => 5,
        'sslverify' => true
    ]);

    if (is_wp_error($response)) {
        error_log('SEO Content Locker - Error al obtener geolocalización: ' . $response->get_error_message());
        return 'Unknown';
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['country_name']) && !empty($data['country_name'])) {
        return sanitize_text_field($data['country_name']);
    }

    // Fallback: intentar obtener al menos el código del país
    if (isset($data['country']) && !empty($data['country'])) {
        return sanitize_text_field($data['country']);
    }

    return 'Unknown';
}

/**
 * Guardar leads vía AJAX
 */
function seocontentlocker_save_lead()
{
    // --- Verificar nonce ---
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'seocontentlocker_nonce')) {
        wp_send_json_error(['message' => __('Invalid request. Please try again.', 'seocontentlocker')]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';

    $email = sanitize_email($_POST['email'] ?? '');
    $slug  = sanitize_text_field($_POST['slug'] ?? '');

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address', 'seocontentlocker')]);
    }

    // Capturar IP real
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($forwarded[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }

    // Obtener país usando ipapi.co
    $country = seocontentlocker_get_country_from_ip($ip);

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
    $inserted = $wpdb->insert(
        $table_name,
        [
            'email'      => $email,
            'ip'         => $ip,
            'country'    => $country,
            'created_at' => current_time('mysql', 1), // UTC
            'post_slug'  => $slug,
        ],
        ['%s', '%s', '%s', '%s', '%s']
    );

    if ($inserted === false) {
        wp_send_json_error([
            'message' => __('Error saving data. Please try again.', 'seocontentlocker')
        ]);
    }

    wp_send_json_success([
        'message' => __('You have successfully subscribed to the 40-day free trial!', 'seocontentlocker')
    ]);
}

// Hooks AJAX
add_action('wp_ajax_seocontentlocker_save_lead', 'seocontentlocker_save_lead');
add_action('wp_ajax_nopriv_seocontentlocker_save_lead', 'seocontentlocker_save_lead');

function seocontentlocker_export_csv()
{
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos suficientes.');
    }
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seocontentlocker_export')) {
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

    fputcsv($output, ['ID', 'Email', 'IP', 'Country', 'Post Slug', 'Fecha']);
    foreach ($results as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

add_action('admin_post_seocontentlocker_export_csv', 'seocontentlocker_export_csv');

function seocontentlocker_delete_lead()
{
    if (!current_user_can('manage_options')) {
        wp_die('No tienes permisos suficientes.');
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_die('ID inválido.');

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seocontentlocker_delete_' . $id)) {
        wp_die('Nonce inválido.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';
    $wpdb->delete($table_name, ['id' => $id], ['%d']);

    wp_redirect(admin_url('admin.php?page=seo-locker&deleted=1'));
    exit;
}
add_action('admin_post_seocontentlocker_delete_lead', 'seocontentlocker_delete_lead');