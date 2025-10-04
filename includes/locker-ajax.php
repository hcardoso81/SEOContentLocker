<?php
if (!defined('ABSPATH')) exit;

/**
 * Obtener país desde IP usando ipapi.co
 */
function seocontentlocker_get_country_from_ip($ip) {
    if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
        return 'Unknown';
    }

    $response = wp_remote_get("https://ipapi.co/{$ip}/json/", [
        'timeout' => 5,
        'sslverify' => true
    ]);

    if (is_wp_error($response)) {
        error_log('SEO Content Locker - Error al obtener geolocalización: ' . $response->get_error_message());
        return 'Unknown';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (!empty($data['country_name'])) {
        return sanitize_text_field($data['country_name']);
    }

    if (!empty($data['country'])) {
        return sanitize_text_field($data['country']);
    }

    return 'Unknown';
}

/**
 * Guardar leads vía AJAX
 */
function seocontentlocker_save_lead() {
    // Verificar nonce
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
     global $_SERVER;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }

    // Obtener país
    $country = seocontentlocker_get_country_from_ip($ip);

    // Verificar duplicado
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_name WHERE email = %s OR ip = %s", $email, $ip)
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
            'status'     => 'pending',
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s']
    );

    // Suscripción a Mailchimp
    $apiKey = get_option('seocontentlocker_mc_api_key');
    $listId = get_option('seocontentlocker_mc_list_id');

    if (!empty($apiKey) && !empty($listId)) {
        if (!function_exists('seocontentlocker_mailchimp_subscribe')) {
            wp_send_json_error(['message' => __('Mailchimp subscription function missing.', 'seocontentlocker')]);
        }

        $result = seocontentlocker_mailchimp_subscribe($apiKey, $listId, $email);

        if (!$result['success']) {
            wp_send_json_error(['message' => __('Error al conectar con Mailchimp', 'seocontentlocker')]);
        }
    }

    wp_send_json_success(['message' => __('Revisa tu email y confirma tu suscripción.', 'seocontentlocker')]);
}

// Hooks AJAX
add_action('wp_ajax_seocontentlocker_save_lead', 'seocontentlocker_save_lead');
add_action('wp_ajax_nopriv_seocontentlocker_save_lead', 'seocontentlocker_save_lead');

/**
 * Exportar leads a CSV
 */
function seocontentlocker_export_csv() {
    if (!current_user_can('manage_options')) wp_die('No tienes permisos suficientes.');
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seocontentlocker_export')) wp_die('Nonce inválido.');

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);

    if (!$results) wp_die('No hay leads para exportar.');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads-' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email', 'IP', 'Country', 'Post Slug', 'Fecha']);
    foreach ($results as $row) fputcsv($output, $row);
    fclose($output);
    exit;
}
add_action('admin_post_seocontentlocker_export_csv', 'seocontentlocker_export_csv');

/**
 * Eliminar lead
 */
function seocontentlocker_delete_lead() {
    if (!current_user_can('manage_options')) wp_die('No tienes permisos suficientes.');

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_die('ID inválido.');

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'seocontentlocker_delete_' . $id)) wp_die('Nonce inválido.');

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';
    $wpdb->delete($table_name, ['id' => $id], ['%d']);

    wp_redirect(admin_url('admin.php?page=seo-locker&deleted=1'));
    exit;
}
add_action('admin_post_seocontentlocker_delete_lead', 'seocontentlocker_delete_lead');

/**
 * Test conexión Mailchimp
 */
add_action('wp_ajax_seocontentlocker_mailchimp_test', 'seocontentlocker_mailchimp_test');
function seocontentlocker_mailchimp_test() {
    if (!current_user_can('manage_options')) wp_send_json_error('No tienes permisos suficientes.');
    check_ajax_referer('seocontentlocker_mailchimp_nonce', 'nonce');

    $api_key = get_option('seocontentlocker_mc_api_key', '');
    if (empty($api_key)) wp_send_json_error('No API key configurada.');
    if (strpos($api_key, '-') === false) wp_send_json_error('Formato de API key inválido. Debe tener el sufijo -usX.');

    list(, $dc) = explode('-', $api_key, 2);
    $url = 'https://' . sanitize_text_field($dc) . '.api.mailchimp.com/3.0/';

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'Authorization' => 'apikey ' . $api_key,
            'Accept' => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) wp_send_json_error($response->get_error_message());

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code === 200) {
        wp_send_json_success(['message' => 'Conexión OK', 'data' => $data]);
    } else {
        $msg = $data['detail'] ?? 'Error de conexión. Código: ' . $code;
        wp_send_json_error($msg);
    }
}
