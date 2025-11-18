<?php if (!defined('ABSPATH')) exit;

function get_country_from_ip($ip) {
    if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'])) return 'Unknown';

    $response = wp_remote_get("https://ipapi.co/{$ip}/json/", ['timeout' => 5, 'sslverify' => true]);
    if (is_wp_error($response)) {
        error_log('SEO Locker GEO Error: ' . $response->get_error_message());
        return 'Unknown';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return sanitize_text_field($data['country_name'] ?? $data['country'] ?? 'Unknown');
}

function get_ip()
{
    if (!isset($_SERVER)) return '';
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
