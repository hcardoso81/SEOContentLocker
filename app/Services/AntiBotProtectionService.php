<?php
namespace SeoContentLocker\Services;

if (!defined('ABSPATH')) exit;

/**
 * Centraliza las protecciones anti-bot de los formularios publicos.
 * El token permite al servidor identificar el formulario real.
 */
class AntiBotProtectionService
{
    const CONTEXT_MAX_AGE = 1800;
    const MIN_SUBMIT_DELAY = 1.0;

    const FORM_SIMPLE = 'subscription_page';
    const FORM_SITE = 'subscription_page_site';
    const FORM_MODAL = 'modal';

    private $recaptchaService;
    private $rateLimitService;

    public function __construct($recaptchaService = null, $rateLimitService = null)
    {
        $this->recaptchaService = $recaptchaService ?: new RecaptchaService();
        $this->rateLimitService = $rateLimitService ?: new RateLimitService();
    }

    public function createFormToken($formType, $isLanding = false)
    {
        if (!$this->isValidFormType($formType)) return '';

        $issuedAt = microtime(true);
        $payload = wp_json_encode([
            'form' => $formType,
            'landing' => (bool) $isLanding,
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt + self::CONTEXT_MAX_AGE,
        ]);
        $encodedPayload = $this->base64UrlEncode($payload);
        $signature = hash_hmac('sha256', $encodedPayload, wp_salt('auth'));

        return $encodedPayload . '.' . $signature;
    }

    public function validateFormToken($token)
    {
        if (!is_string($token) || $token === '') return null;

        $parts = explode('.', $token, 2);
        if (count($parts) !== 2 || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) return null;

        $expectedSignature = hash_hmac('sha256', $parts[0], wp_salt('auth'));
        if (!hash_equals($expectedSignature, $parts[1])) return null;

        $payload = json_decode($this->base64UrlDecode($parts[0]), true);
        if (!is_array($payload) || !$this->isValidFormType($payload['form'] ?? '')) return null;

        $issuedAt = $payload['issued_at'] ?? null;
        $expiresAt = $payload['expires_at'] ?? null;
        $now = microtime(true);
        if (!is_numeric($issuedAt) || !is_numeric($expiresAt)) return null;
        if ((float) $expiresAt <= (float) $issuedAt || $now > (float) $expiresAt) return null;
        if ((float) $issuedAt > $now + 5) return null;

        return [
            'form' => $payload['form'],
            'landing' => !empty($payload['landing']),
            'issued_at' => (float) $issuedAt,
            'expires_at' => (float) $expiresAt,
            'elapsed_seconds' => max(0, $now - (float) $issuedAt),
        ];
    }

    public function validate($context, $honeypot, $consent, $recaptchaToken, $ip = '', $email = '')
    {
        if (!is_array($context)) {
            $this->reject(__('The form submission could not be verified.', 'seocontentlocker'));
        }

        $rateLimit = $this->rateLimitService->check($ip, $context['form'], $email);
        if (empty($rateLimit['allowed'])) {
            $this->reject(__('The form submission could not be processed right now. Please try again later.', 'seocontentlocker'), 429);
        }

        if (($context['elapsed_seconds'] ?? 0) < self::MIN_SUBMIT_DELAY) {
            $this->reject(__('The form submission could not be verified.', 'seocontentlocker'));
        }

        if (trim((string) $honeypot) !== '') {
            $this->reject(__('The form submission was rejected.', 'seocontentlocker'));
        }

        if ($this->requiresConsent($context['form'])) {
            $consentValue = is_scalar($consent) ? (string) $consent : '';
            if ($consentValue !== '1') {
                $this->reject(__('Consent is required.', 'seocontentlocker'));
            }
        }

        if ($this->requiresRecaptcha($context['form'])) {
            $this->recaptchaService->validate($recaptchaToken, $this->getExpectedRecaptchaAction($context['form']));
        }
    }

    public function requiresConsent($formType)
    {
        return in_array($formType, [self::FORM_SITE, self::FORM_MODAL], true);
    }

    public function requiresRecaptcha($formType)
    {
        return $this->isValidFormType($formType);
    }

    public function getExpectedRecaptchaAction($formType)
    {
        $actions = [
            self::FORM_SIMPLE => 'subscription_simple_submit',
            self::FORM_SITE => 'subscription_site_submit',
            self::FORM_MODAL => 'locker_modal_submit',
        ];

        return $actions[$formType] ?? '';
    }

    private function isValidFormType($formType)
    {
        return in_array($formType, [self::FORM_SIMPLE, self::FORM_SITE, self::FORM_MODAL], true);
    }

    private function reject($message, $status = 200)
    {
        wp_send_json_error(['message' => $message], $status);
        wp_die();
    }

    private function base64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode($value)
    {
        $padding = strlen($value) % 4;
        if ($padding) $value .= str_repeat('=', 4 - $padding);

        return base64_decode(strtr($value, '-_', '+/'), true) ?: '';
    }
}
