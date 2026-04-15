<?php
if (!defined('ABSPATH')) exit;

function check_lead($email, $slug = null)
{
    $lead = seocontentlocker_lead_repository()->findByEmail($email);
    $now = new DateTime();

    if (!$lead || !$lead->expires_at) {
        return null;
    }

    $expireAt = new DateTime($lead->expires_at);

    if ($now > $expireAt) {
        log_expires($email);

        seocontentlocker_dispatch_event(
            SeoContentLockerEvents::LEAD_EXPIRED,
            [
                'email' => $email,
                'slug'  => $slug,
            ]
        );

        return [
            'status' => 'expired',
            'message' => 'Your access period has expired',
        ];
    }

    if (!$slug) {
        log_restore($email);

        seocontentlocker_dispatch_event(
            SeoContentLockerEvents::LEAD_RESTORED,
            [
                'email' => $email,
                'slug'  => $slug,
            ]
        );
    } else {
        log_access($email, $slug);
    }

    return [
        'status' => 'restored',
        'message' => 'Access restored. Welcome back!',
    ];
}

function check_ip($ip, $email, $slug = null, $insert_same_ip = true)
{
    $existingIp = seocontentlocker_lead_repository()->findByIp($ip);

    if (!$existingIp) {
        return null;
    }

    $country = get_country_from_ip($ip);

    if ($insert_same_ip) {
        log_same_ip($ip, $country, $existingIp->email, $email, $slug);
        seocontentlocker_same_ip_repository()->insert($ip, $country, $email, $slug);
    }

    seocontentlocker_dispatch_event(
        SeoContentLockerEvents::SAME_IP_BLOCKED,
        [
            'email' => $email,
            'ip' => $ip,
            'existing_email' => $existingIp->email,
            'slug' => $slug,
        ]
    );

    return [
        'status' => 'expired',
        'message' => 'Multiple leads from same IP',
    ];
}

function save_lead($email, $slug, $ip = null)
{
    $ip = $ip ?: get_ip();
    $country = get_country_from_ip($ip);

    log_suscription($email, $ip, $country);

    return seocontentlocker_lead_repository()->insert($email, $ip, $country, $slug);
}

function validateRecaptcha($recaptcha)
{
    if (empty($recaptcha)) {
        wp_send_json_error([
            'message' => 'Captcha missing',
        ]);
        wp_die();
    }

    $secret = get_option('seocontentlocker_recaptcha_secret_key');
    $verify = wp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptcha}");

    if (is_wp_error($verify)) {
        wp_send_json_error(['message' => 'Captcha validation failed']);
        wp_die();
    }

    $verified = json_decode(wp_remote_retrieve_body($verify));

    if (empty($verified->success)) {
        wp_send_json_error([
            'message' => 'Captcha invalid',
        ]);
        wp_die();
    }
}
