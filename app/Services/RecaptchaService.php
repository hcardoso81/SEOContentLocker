<?php
namespace SeoContentLocker\Services;

if (!defined('ABSPATH')) exit;

class RecaptchaService
{
    const DEFAULT_THRESHOLD = 0.5;

    public function validate($token, $expectedAction)
    {
        if (empty($token) || empty($expectedAction)) {
            $this->reject();
        }

        $secret = trim((string) get_option('seocontentlocker_recaptcha_v3_secret_key', ''));
        if ($secret === '') {
            $this->reject();
        }

        $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'timeout' => 10,
            'body' => [
                'secret' => $secret,
                'response' => $token,
            ],
        ]);

        if (is_wp_error($verify) || (int) wp_remote_retrieve_response_code($verify) !== 200) {
            $this->reject();
        }

        $verified = json_decode(wp_remote_retrieve_body($verify), true);
        if (!is_array($verified) || ($verified['success'] ?? false) !== true) {
            $this->reject();
        }

        $score = $verified['score'] ?? null;
        if (!is_numeric($score) || (float) $score < $this->getThreshold()) {
            $this->reject();
        }

        if (($verified['action'] ?? '') !== $expectedAction) {
            $this->reject();
        }

        $expectedHostname = $this->normalizeHostname(parse_url(home_url(), PHP_URL_HOST));
        $verifiedHostname = $this->normalizeHostname($verified['hostname'] ?? '');
        if ($expectedHostname === '' || $verifiedHostname === '' || $expectedHostname !== $verifiedHostname) {
            $this->reject();
        }
    }

    public function getThreshold()
    {
        $threshold = get_option('seocontentlocker_recaptcha_threshold', self::DEFAULT_THRESHOLD);
        if (!is_numeric($threshold)) {
            return self::DEFAULT_THRESHOLD;
        }

        return (float) max(0, min(1, $threshold));
    }

    private function normalizeHostname($hostname)
    {
        $hostname = strtolower(trim((string) $hostname));
        return preg_replace('/^www\./', '', $hostname);
    }

    private function reject()
    {
        wp_send_json_error([
            'message' => __('The form submission could not be verified.', 'seocontentlocker'),
        ]);
        wp_die();
    }
}
