<?php
if (!defined('ABSPATH')) exit;

function get_country_from_ip($ip) {
    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=country");
    if (is_wp_error($response)) return '';

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['country'] ?? '';
}

function get_ip()
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            return sanitize_text_field(explode(',', $_SERVER[$key])[0]);
        }
    }

    return '';
}
