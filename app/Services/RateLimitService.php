<?php
namespace SeoContentLocker\Services;

if (!defined('ABSPATH')) exit;

/**
 * Limita la frecuencia de submits sin mezclarla con la regla de negocio de IP.
 * Usa transients para funcionar con y sin object cache persistente.
 */
class RateLimitService
{
    const IP_FORM_LIMIT = 5;
    const IP_FORM_WINDOW = 300;
    const IP_LIMIT = 10;
    const IP_WINDOW = 900;
    const EMAIL_LIMIT = 3;
    const EMAIL_WINDOW = 600;

    public function check($ip, $formType, $email = '')
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            $this->logStorageFailure('missing_ip', $formType);
            return true;
        }

        $checks = [
            [
                'type' => 'ip_form',
                'value' => $ip . '|' . $formType,
                'limit' => self::IP_FORM_LIMIT,
                'window' => self::IP_FORM_WINDOW,
            ],
            [
                'type' => 'ip',
                'value' => $ip,
                'limit' => self::IP_LIMIT,
                'window' => self::IP_WINDOW,
            ],
        ];

        $normalizedEmail = sanitize_email($email);
        if ($normalizedEmail !== '') {
            $checks[] = [
                'type' => 'email',
                'value' => strtolower($normalizedEmail),
                'limit' => self::EMAIL_LIMIT,
                'window' => self::EMAIL_WINDOW,
            ];
        }

        foreach ($checks as $check) {
            $result = $this->consume($check);
            if ($result === false) {
                log_rate_limit($check['type'], $formType, $this->hashValue($ip), $this->hashValue($normalizedEmail));
                return [
                    'allowed' => false,
                    'type' => $check['type'],
                ];
            }
        }

        return ['allowed' => true];
    }

    private function consume($check)
    {
        $key = 'scl_rl_' . $this->hashValue($check['value']);
        $now = time();
        $stored = get_transient($key);

        if (is_wp_error($stored)) {
            $this->logStorageFailure('read', $check['type']);
            return true;
        }

        $timestamps = is_array($stored) ? $stored : [];
        $timestamps = array_values(array_filter($timestamps, function ($timestamp) use ($now, $check) {
            return is_numeric($timestamp) && ((int) $timestamp > $now - $check['window']);
        }));

        if (count($timestamps) >= $check['limit']) {
            return false;
        }

        $timestamps[] = $now;
        if (!set_transient($key, $timestamps, $check['window'])) {
            $this->logStorageFailure('write', $check['type']);
            return true;
        }

        return true;
    }

    private function hashValue($value)
    {
        return hash_hmac('sha256', (string) $value, wp_salt('auth'));
    }

    private function logStorageFailure($operation, $type)
    {
        if (function_exists('log_rate_limit_storage_failure')) {
            log_rate_limit_storage_failure($operation, $type);
        }
    }
}
