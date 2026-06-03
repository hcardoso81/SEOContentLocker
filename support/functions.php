<?php
if (!defined('ABSPATH')) exit;

function locker_component($name, $vars = [])
{
    if (!empty($vars) && is_array($vars)) {
        extract($vars, EXTR_SKIP);
    }

    include plugin_dir_path(__FILE__) . "../templates/{$name}.php";
}

function validate_nonce($nonce_field, $action_name, $ajax = true)
{
    $nonce = $_POST[$nonce_field] ?? '';

    if (!$nonce || !wp_verify_nonce($nonce, $action_name)) {
        $message = __('Invalid request (nonce check failed).', 'seocontentlocker');
        if ($ajax) {
            wp_send_json_error(['message' => $message]);
        } else {
            wp_die($message);
        }
    }

    return true;
}

function validateEmail($email, $ajax = true)
{
    $email = sanitize_email($email);

    if (empty($email) || !is_email($email)) {
        $message = __('Invalid email address.', 'seocontentlocker');
        if ($ajax) {
            wp_send_json_error(['message' => $message]);
        } else {
            wp_die($message);
        }
    }

    return $email;
}

function seocontentlocker_normalize_slug($slug)
{
    $slug = sanitize_text_field($slug);
    $path = wp_parse_url($slug, PHP_URL_PATH);

    if ($path !== null && $path !== false) {
        $slug = $path;
    }

    return trim(untrailingslashit($slug), "/ \t\n\r\0\x0B");
}

function seocontentlocker_request_slug($slug)
{
    $slug = seocontentlocker_normalize_slug($slug);

    if ($slug) {
        return $slug;
    }

    $referer = wp_get_referer();
    if (!$referer && !empty($_SERVER['HTTP_REFERER'])) {
        $referer = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
    }

    return seocontentlocker_normalize_slug($referer);
}

function verify_permission($capability = 'manage_options', $ajax = false)
{
    if (!current_user_can($capability)) {
        $message = __('You do not have sufficient permissions to perform this action.', 'seo-locker');

        if ($ajax) {
            wp_send_json_error(['message' => $message]);
        } else {
            wp_die(esc_html($message));
        }
    }
}
