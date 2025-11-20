<?php

if (!defined('ABSPATH')) exit;

function check_lead($email, $slug = null)
{
    $lead = db_get_lead_by_email($email);
    $now  = new DateTime();

    if ($lead && $lead->expires_at) {
        // convertir string → DateTime
        $expire_at = new DateTime($lead->expires_at);

        if ($now > $expire_at) {
            log_expires($email);
            wp_send_json_success([
                'message' => 'Your access period has expired',
                'status'  => 'expired',
            ]);
            wp_die();
        }

        if ($now <= $expire_at) {
            if (!$slug) {
                log_restore($email);
            } else {
                log_access($email, $slug);
            }
            wp_send_json_success([
                'message' => 'Access restored. Welcome back!',
                'status'  => 'restored',
            ]);
            wp_die();
        }
    }

    return true;
}

function check_ip($ip, $email, $insert_same_ip = true)
{
    $existing_ip = db_get_lead_by_ip($ip);

    if ($existing_ip) {
        if ($insert_same_ip) {
            $country = get_country_from_ip($ip);
            $slug  = sanitize_text_field($_POST['slug'] ?? '');
            log_same_ip($ip, $country,  $existing_ip->email, $email, $slug);
            db_insert_same_ip($ip, $country,$email, $slug);
        }

        wp_send_json_success([
            'message' => 'Your access period has expired',
            'status'  => 'expired',
        ]);
        wp_die();
    }

    return true;
}

function save_lead($email)
{
    $slug  = sanitize_text_field($_POST['slug'] ?? '');
    $ip    = get_ip();
    $country = get_country_from_ip($ip);

    log_suscription($email, $ip, $country);
    db_insert_lead($email, $ip, $country, $slug);
}

function validateRecaptcha()
{


    $recaptcha = sanitize_text_field($_POST['g-recaptcha-response'] ?? '');

    if (empty($recaptcha)) {
        wp_send_json_error([
            'message' => 'Captcha missing'
        ]);
        wp_die();
    }

    // SECRET KEY guardada en opciones
    $secret = get_option('seocontentlocker_recaptcha_secret_key');

    // Llamado a Google
    $verify = wp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$recaptcha}");

    if (is_wp_error($verify)) {
        wp_send_json_error(['message' => 'Captcha validation failed']);
        wp_die();
    }

    $verified = json_decode(wp_remote_retrieve_body($verify));

    if (empty($verified->success)) {
        wp_send_json_error([
            'message' => 'Captcha invalid'
        ]);
        wp_die();
    }
}
