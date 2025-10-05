<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'locker-utils.php';
require_once plugin_dir_path(__FILE__) . 'locker-db.php';
require_once plugin_dir_path(__FILE__) . 'locker-geo.php';
require_once plugin_dir_path(__FILE__) . 'locker-mailchimp-admin.php';

/**
 * ========================
 * AJAX: Guardar lead
 * ========================
 */
add_action('wp_ajax_seocontentlocker_save_lead', 'seocontentlocker_save_lead');
add_action('wp_ajax_nopriv_seocontentlocker_save_lead', 'seocontentlocker_save_lead');

function seocontentlocker_save_lead()
{
    // --- Validaciones básicas ---
    seocontentlocker_validate_nonce('nonce', 'seocontentlocker_nonce', true);
    $email = seocontentlocker_validate_email($_POST['email'] ?? '', true);
    $slug  = sanitize_text_field($_POST['slug'] ?? '');
    $ip    = seocontentlocker_get_ip();
    $country = seocontentlocker_get_country_from_ip($ip);

    // --- Verificar si el lead ya completó su trial ---
    if (seocontentlocker_db_exists($email, $ip, 'confirmed')) {
        wp_send_json_error([
            'message' => __('Your free trial period has already ended.', 'seocontentlocker'),
            'trialExpired' => true
        ]);
    }

    // --- Insertar lead pendiente (si no existe) ---
    if (!seocontentlocker_db_exists($email, $ip, 'pending')) {
        seocontentlocker_db_insert_pending($email, $ip, $country, $slug);
    }

    // --- Suscribir a Mailchimp ---
    $mailchimp = seocontentlocker_handle_mailchimp_subscription($email);
    if (!$mailchimp['success']) wp_send_json_error(['message' => $mailchimp['message']]);

    // --- Respuesta según estado ---
    if ($mailchimp['status'] === 'pending') {
        wp_send_json_error(['message' => $mailchimp['message'], 'pending' => true]);
    }

    // --- Confirmar lead ---
    seocontentlocker_db_update_confirmed($email);
    wp_send_json_success(['message' => $mailchimp['message']]);
}

/**
 * ========================
 * Mailchimp integration
 * ========================
 */
function seocontentlocker_handle_mailchimp_subscription($email)
{
    $apiKey = get_option('seocontentlocker_mc_api_key');
    $listId = get_option('seocontentlocker_mc_list_id');

    if (empty($apiKey) || empty($listId)) {
        return ['success' => false, 'message' => __('Mailchimp configuration missing', 'seocontentlocker')];
    }

    if (!function_exists('seocontentlocker_mailchimp_subscribe')) {
        return ['success' => false, 'message' => __('Mailchimp subscription function missing', 'seocontentlocker')];
    }

    $result = seocontentlocker_mailchimp_subscribe($apiKey, $listId, $email);

    if (!$result['success']) {
        return ['success' => false, 'message' => __('Error connecting to Mailchimp', 'seocontentlocker')];
    }


   switch ($result['status']) {
        case 'pending':
            return [
                'success' => true,
                'status'  => 'pending',
                'message' => __('Please confirm your email to unlock the content.', 'seocontentlocker')
            ];

        case 'subscribed':
            return [
                'success' => true,
                'status'  => 'subscribed',
                'message' => __('Subscription confirmed! Content unlocked.', 'seocontentlocker')
            ];

        default:
            return [
                'success' => false,
                'message' => __('Unexpected Mailchimp response', 'seocontentlocker')
            ];
    }
}

/**
 * ========================
 * Exportar leads a CSV
 * ========================
 */
add_action('admin_post_seocontentlocker_export_csv', 'seocontentlocker_export_csv');

function seocontentlocker_export_csv()
{
    seocontentlocker_verify_permission('manage_options');
    seocontentlocker_validate_nonce('_wpnonce', 'seocontentlocker_export', false);

    $results = seocontentlocker_db_get_all();
    if (empty($results)) wp_die(__('No leads found for export.', 'seocontentlocker'));

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=leads-' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email', 'IP', 'Country', 'Post Slug', 'Created At', 'Status']);
    foreach ($results as $row) fputcsv($output, $row);
    fclose($output);
    exit;
}

/**
 * ========================
 * Eliminar lead
 * ========================
 */
add_action('admin_post_seocontentlocker_delete_lead', 'seocontentlocker_delete_lead');

function seocontentlocker_delete_lead()
{
    seocontentlocker_verify_permission('manage_options');

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_die(__('Invalid lead ID.', 'seocontentlocker'));

    seocontentlocker_validate_nonce('_wpnonce', 'seocontentlocker_delete_' . $id, false);

    seocontentlocker_db_delete($id);

    wp_redirect(admin_url('admin.php?page=seo-locker&deleted=1'));
    exit;
}

/**
 * ========================
 * Test conexión Mailchimp
 * ========================
 */
add_action('wp_ajax_seocontentlocker_mailchimp_test', 'seocontentlocker_mailchimp_test');

function seocontentlocker_mailchimp_test()
{
    seocontentlocker_verify_permission('manage_options', true);
    seocontentlocker_validate_nonce('nonce', 'seocontentlocker_mailchimp_nonce', true);

    $api_key = get_option('seocontentlocker_mc_api_key', '');
    if (empty($api_key)) wp_send_json_error(__('No API key configured.', 'seocontentlocker'));
    if (!str_contains($api_key, '-')) wp_send_json_error(__('Invalid API key format (missing -usX suffix).', 'seocontentlocker'));

    list(, $dc) = explode('-', $api_key, 2);
    $url = 'https://' . sanitize_text_field($dc) . '.api.mailchimp.com/3.0/';

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => [
            'Authorization' => 'apikey ' . $api_key,
            'Accept'        => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) wp_send_json_error($response->get_error_message());

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code === 200) {
        wp_send_json_success(['message' => __('Connection successful.', 'seocontentlocker'), 'data' => $data]);
    } else {
        $msg = $data['detail'] ?? sprintf(__('Connection error. Code: %d', 'seocontentlocker'), $code);
        wp_send_json_error($msg);
    }
}
