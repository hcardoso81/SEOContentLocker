<?php
if (!defined('ABSPATH')) exit;

function seo_locker_save_lead() {
    check_ajax_referer('seo_locker_nonce', 'nonce');

    if (empty($_POST['email'])) {
        wp_send_json_error(['message' => 'Email requerido']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';

    $wpdb->insert($table_name, [
        'email'     => sanitize_email($_POST['email']),
        'post_slug' => sanitize_text_field($_POST['slug']),
    ]);

    // Guardar cookie 7 días
    setcookie('seo_locker_access', '1', time() + (7 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);

    wp_send_json_success(['message' => 'Acceso concedido']);
}
add_action('wp_ajax_seo_locker_save_lead', 'seo_locker_save_lead');
add_action('wp_ajax_nopriv_seo_locker_save_lead', 'seo_locker_save_lead');
