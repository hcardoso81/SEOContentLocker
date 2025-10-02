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
