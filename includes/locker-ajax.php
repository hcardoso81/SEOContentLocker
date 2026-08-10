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
    validate_nonce('nonce', 'seocontentlocker_nonce', true);

    $email = validateEmail($_POST['email'] ?? '', true);
    $slug  = seocontentlocker_request_slug($_POST['slug'] ?? '');
    $ip    = get_ip();

    try {
        $service = new \SeoContentLocker\Services\LeadAccessService();
        $result = $service->checkStatus($email, $slug, $ip);

        wp_send_json_success($result);
    } catch (Exception $e) {
        log_error($e, 'check_lead_ajax', $email);

        wp_send_json_error([
            'message' => 'check lead: An unexpected error occurred.'
        ]);
    }

    wp_die();
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
    validate_nonce('nonce', 'seocontentlocker_nonce', true);

    $email = validateEmail($_POST['email'] ?? '', true);
    $firstName = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
    if ($firstName === '') {
        wp_send_json_error(['message' => __('First name is required.', 'seocontentlocker')]);
    }
    $ip = get_ip();

    $antiBotService = new \SeoContentLocker\Services\AntiBotProtectionService();
    $formContext = $antiBotService->validateFormToken(
        sanitize_text_field(wp_unslash($_POST['locker_form_token'] ?? ''))
    );
    $antiBotService->validate(
        $formContext,
        sanitize_text_field(wp_unslash($_POST['locker_website'] ?? '')),
        sanitize_text_field(wp_unslash($_POST['consent'] ?? '')),
        sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'] ?? '')),
        $ip,
        $email
    );

    $slug  = seocontentlocker_request_slug($_POST['slug'] ?? '');
    $isLanding = !empty($formContext['landing']);
    $notifyRestore = true;

    try {
        $service = new \SeoContentLocker\Services\LeadRegistrationService();
        $result = $service->register($email, $firstName, $slug, $ip, $notifyRestore, $isLanding);

        wp_send_json_success($result);
        wp_die();
    } catch (Exception $e) {
        log_error($e, 'save_lead_ajax', $email);

        wp_send_json_error([
            'message' => 'save lead: An unexpected error occurred.'
        ]);

        wp_die();
    }
}
