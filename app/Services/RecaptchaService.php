<?php
namespace SeoContentLocker\Services;

if (!defined('ABSPATH')) exit;

class RecaptchaService
{
    public function validate($token)
    {
        if (empty($token)) {
            wp_send_json_error([
                'message' => 'Captcha missing',
            ]);
            wp_die();
        }

        $secret = get_option('seocontentlocker_recaptcha_secret_key');
        $verify = wp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret={$secret}&response={$token}");

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
}
