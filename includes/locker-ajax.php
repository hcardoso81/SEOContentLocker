<?php
if (!defined('ABSPATH')) exit;

/**
 * Guardar leads vía AJAX
 */
function imf_save_lead()
{
    // --- Verificar nonce ---
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imf_nonce')) {
        wp_send_json_error(['message' => __('Invalid request. Please try again.', 'imf')]);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';

    $email = sanitize_email($_POST['email']);
    $slug  = sanitize_text_field($_POST['slug']);

    if (!is_email($email)) {
        wp_send_json_error(['message' => __('Invalid email address', 'imf')]);
    }

    // Verificar si existe
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email)
    );

    if ($exists) {
        wp_send_json_error([
            'message' => __('Your free trial period has already ended.', 'imf'),
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
        'message' => __('You have successfully subscribed to the 40-day free trial!', 'imf')
    ]);
}

// Hook AJAX
add_action('wp_ajax_imf_save_lead', 'imf_save_lead');
add_action('wp_ajax_nopriv_imf_save_lead', 'imf_save_lead');
