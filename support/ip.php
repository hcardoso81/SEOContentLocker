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
    $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!filter_var($remoteAddress, FILTER_VALIDATE_IP)) {
        return '';
    }

    $trustedProxies = defined('SEO_CONTENT_LOCKER_TRUSTED_PROXY_IPS')
        ? constant('SEO_CONTENT_LOCKER_TRUSTED_PROXY_IPS')
        : [];

    if (!is_array($trustedProxies) || !in_array($remoteAddress, $trustedProxies, true)) {
        return $remoteAddress;
    }

    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }

        foreach (explode(',', $_SERVER[$key]) as $candidate) {
            $candidate = trim($candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }
    }

    return $remoteAddress;
}
