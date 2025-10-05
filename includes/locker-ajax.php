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

function seocontentlocker_save_lead() {
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

    // --- Insertar lead pendiente si no existe ---
    if (!seocontentlocker_db_exists($email, $ip, 'pending')) {
        seocontentlocker_db_insert_pending($email, $ip, $country, $slug);
    }

    // --- Suscribir a Mailchimp (o revisar estado existente) ---
    $apiKey = get_option('seocontentlocker_mc_api_key');
    $listId = get_option('seocontentlocker_mc_list_id');

    $mailchimp = seocontentlocker_handle_mailchimp_subscription($apiKey, $listId, $email);

    if (!$mailchimp['success']) {
        wp_send_json_error(['message' => $mailchimp['message']]);
    }

    // --- Respuesta según estado ---
    if ($mailchimp['status'] === 'pending') {
        // El usuario ya está pendiente, podemos informar o incluso reenviar el email de confirmación
        wp_send_json_error([
            'message' => $mailchimp['message'],
            'pending' => true
        ]);
    }

    // --- Confirmar lead en nuestra DB ---
    seocontentlocker_db_update_confirmed($email);

    wp_send_json_success(['message' => $mailchimp['message']]);
}

/**
 * ========================
 * Mailchimp integration
 * ========================
 */
function seocontentlocker_handle_mailchimp_subscription($apiKey, $listId, $email) {
    if (empty($apiKey) || empty($listId)) {
        return ['success' => false, 'message' => __('Mailchimp configuration missing', 'seocontentlocker')];
    }

    // --- Primero consultamos si el miembro ya existe ---
    $existing = seocontentlocker_get_mailchimp_member($apiKey, $listId, $email);
    if ($existing) {
        switch ($existing['status']) {
            case 'pending':
                return seocontentlocker_mailchimp_resend_confirmation($apiKey, $listId, $email);
            case 'subscribed':
                return [
                    'success' => true,
                    'status'  => 'subscribed',
                    'message' => __('Subscription confirmed! Content unlocked.', 'seocontentlocker')
                ];
        }
    }

    // --- Si no existe, suscribimos normalmente ---
    $result = seocontentlocker_mailchimp_subscribe($apiKey, $listId, $email);

    if (!$result['success']) {
        return ['success' => false, 'message' => __('Error connecting to Mailchimp', 'seocontentlocker')];
    }

    return [
        'success' => true,
        'status'  => $result['status'],
        'message' => $result['status'] === 'pending' ?
            __('Please check your email to confirm your subscription.', 'seocontentlocker') :
            __('Subscription confirmed! Content unlocked.', 'seocontentlocker')
    ];
}


