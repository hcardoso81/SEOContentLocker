<?php
if (!defined('ABSPATH')) exit;

function seocontentlocker_get_country_from_ip($ip) {
    if (empty($ip) || in_array($ip, ['127.0.0.1', '::1'])) return 'Unknown';

    $response = wp_remote_get("https://ipapi.co/{$ip}/json/", ['timeout' => 5, 'sslverify' => true]);
    if (is_wp_error($response)) {
        error_log('SEO Locker GEO Error: ' . $response->get_error_message());
        return 'Unknown';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return sanitize_text_field($data['country_name'] ?? $data['country'] ?? 'Unknown');
}
