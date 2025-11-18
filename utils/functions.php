<?php
if (!defined('ABSPATH')) exit;


function locker_component($name)
{
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

/**
 * Valida y limpia un email
 *
 * @param string $email El email a validar
 * @param bool $ajax Si es true, responde con wp_send_json_error; si es false, hace wp_die()
 * @return string Email validado y saneado
 */
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


/**
 * Verifica si el usuario actual tiene los permisos necesarios.
 *
 * @param string $capability (opcional) La capacidad a verificar. Por defecto: 'manage_options'.
 * @param bool $ajax (opcional) Si se está usando dentro de una llamada AJAX. Por defecto: false.
 */
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
