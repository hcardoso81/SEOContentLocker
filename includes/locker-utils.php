<?php

/**
 * Verifica un nonce pasado por parámetro
 *
 * @param string $nonce_field Nombre del campo que viene en $_POST
 * @param string $action_name Nombre de la acción usada al generar el nonce
 * @param bool $ajax Si es true, responde con wp_send_json_error; si es false, hace wp_die()
 */
function seocontentlocker_validate_nonce($nonce_field, $action_name, $ajax = true) {
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
function seocontentlocker_validate_email($email, $ajax = true) {
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
function seocontentlocker_verify_permission($capability = 'manage_options', $ajax = false) {
    if (!current_user_can($capability)) {
        $message = __('You do not have sufficient permissions to perform this action.', 'seo-locker');

        if ($ajax) {
            wp_send_json_error(['message' => $message]);
        } else {
            wp_die(esc_html($message));
        }
    }
}



/**
 * Devuelve IP del visitante (considerando proxies)
 */
function seocontentlocker_get_ip() {
    if(!isset($_SERVER)) return '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ip_list[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function seocontentlocker_mailchimp_subscribe($apiKey, $listId, $email) {
    $dc = substr($apiKey, strpos($apiKey, '-') + 1);
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/";

    $body = [
        'email_address' => $email,
        'status'        => 'pending' // Double opt-in
    ];


    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'apikey ' . $apiKey,
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode($body)
    ]);

    if (is_wp_error($response)) {
        return ['success' => false, 'error' => $response->get_error_message()];
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return [
        'success' => ($code == 200 || $code == 201),
        'status'  => $body['status'] ?? 'pending'
    ];
}


/**
 * Obtiene un miembro de Mailchimp por email
 */
function seocontentlocker_get_mailchimp_member($apiKey, $listId, $email) {
    $dc = substr($apiKey, strpos($apiKey, '-') + 1);
    $subscriberHash = md5(strtolower($email));
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'apikey ' . $apiKey,
            'Accept'        => 'application/json'
        ]
    ]);

    if (is_wp_error($response)) return false;

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    return ($code === 200) ? $body : false;
}


/**
 * Reenvía el email de confirmación de Mailchimp para un lead existente en estado 'pending'
 *
 * @param string $apiKey Mailchimp API key
 * @param string $listId ID de la lista
 * @param string $email Email del suscriptor
 * @return array Resultado con success, status y message
 */
function seocontentlocker_mailchimp_resend_confirmation($apiKey, $listId, $email) {
    if (empty($apiKey) || empty($listId) || empty($email)) {
        return [
            'success' => false,
            'status'  => 'pending',
            'message' => __('Missing required parameters for Mailchimp confirmation resend.', 'seocontentlocker')
        ];
    }

    $dc = substr($apiKey, strpos($apiKey, '-') + 1);
    $subscriberHash = md5(strtolower($email));

    // Primero obtener el estado actual del miembro
    $member = seocontentlocker_get_mailchimp_member($apiKey, $listId, $email);
    if (!$member) {
        return [
            'success' => false,
            'status'  => 'not_found',
            'message' => __('Subscriber not found in Mailchimp list.', 'seocontentlocker')
        ];
    }

    if ($member['status'] !== 'pending') {
        return [
            'success' => false,
            'status'  => $member['status'],
            'message' => sprintf(
                __('Cannot resend confirmation email because subscriber status is "%s".', 'seocontentlocker'),
                $member['status']
            )
        ];
    }

    // Ahora sí, reenviamos
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}/actions/resend-confirmation";

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'apikey ' . $apiKey,
            'Content-Type'  => 'application/json'
        ],
        'body' => json_encode([])
    ]);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'status'  => 'pending',
            'message' => __('Error resending confirmation email: ', 'seocontentlocker') . $response->get_error_message()
        ];
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code == 204) {
        return [
            'success' => true,
            'status'  => 'pending',
            'message' => __('Confirmation email resent. Please check your inbox.', 'seocontentlocker')
        ];
    }

    $body = wp_remote_retrieve_body($response);
    return [
        'success' => false,
        'status'  => 'pending',
        'message' => __('Failed to resend confirmation email.', 'seocontentlocker') . ' Response: ' . $body
    ];
}
