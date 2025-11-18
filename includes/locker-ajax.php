<?php if (!defined('ABSPATH')) exit;

/**
 * ========================
 * AJAX: Check lead status
 * ========================
 */

add_action('wp_ajax_nopriv_seocontentlocker_check_lead_status', 'seocontentlocker_check_lead_status');
add_action('wp_ajax_seocontentlocker_check_lead_status', 'seocontentlocker_check_lead_status');

function seocontentlocker_check_lead_status()
{
    $email = validateEmail($_POST['email'] ?? '', true);
    $slug  = sanitize_text_field($_POST['slug'] ?? '');
    try {

        check_lead($email, $slug);
        check_ip(get_ip(), $email, false);
        wp_send_json_success([
            'message'     => 'checked lead status',
            'status'      => 'success',
        ]);

        wp_die();
    } catch (Exception $e) {
        log_error($e, 'check_lead_ajax', $email);
        wp_send_json_error(['message' => 'check lead: An unexpected error occurred.']);
        wp_die();
    }
}


/**
 * ========================
 * AJAX: Guardar lead
 * ========================
 */

add_action('wp_ajax_nopriv_seocontentlocker_save_lead', 'seocontentlocker_save_lead');
add_action('wp_ajax_seocontentlocker_save_lead', 'seocontentlocker_save_lead');

function seocontentlocker_save_lead()
{
    $email = validateEmail($_POST['email'] ?? '', true);

    try {

        // Validaciones previas
        check_lead($email);
        $ip = get_ip();
        check_ip($ip, $email);

        // Guardar lead local
        save_lead($email);

        // 🔥 SUSCRIPCIÓN MAILCHIMP (antes de enviar JSON)
        $slug = sanitize_text_field($_POST['slug'] ?? '');
        $mcResponse = seocontentlocker_mailchimp_subscribe($email, $slug);

        if (!$mcResponse['success']) {
            log_error(
                'Mailchimp subscription failed',
                'mailchimp_subscribe',
                [
                    'email' => $email,
                    'mailchimp_error' => $mcResponse
                ]
            );
        }

        // 👉 recién ahora se responde al frontend
        wp_send_json_success([
            'message' => 'Subscription processed',
            'status'  => $mcResponse['success'] ? 'success' : 'mailchimp_failed',
            'mc'      => $mcResponse
        ]);

        wp_die();

    } catch (Exception $e) {
        log_error($e, 'save_lead_ajax', $email);

        wp_send_json_error([
            'message' => 'save lead: An unexpected error occurred.'
        ]);

        wp_die();
    }
}
