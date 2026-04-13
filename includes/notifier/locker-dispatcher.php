<?php
if (!defined('ABSPATH')) exit;

class SeoContentLockerDispatcher
{
    private $notifier;

    public function __construct()
    {
        $email = LOCKER_REPORT_EMAIL;

        if (!$email) {
            $this->notifier = null;
            return;
        }

        $this->notifier = new SeoContentLockerNotifier($email);
    }

    public function dispatch($event, $data = [])
    {

        if (!$this->notifier) return;

        $email = $data['email'] ?? '';

        // 🔥 NUEVO: filtro anti-spam
        if (!seocontentlocker_should_notify($event, $email)) {
            return;
        }

        switch ($event) {

            case SeoContentLockerEvents::LEAD_CREATED_SUCCESS:
                $this->notifier->send(
                    '✅ Lead creado + Mailchimp OK',
                    'Nuevo Lead ',
                    $data
                );
                break;

            case SeoContentLockerEvents::MAILCHIMP_FAILED:
                $this->notifier->send(
                    '⚠️ Mailchimp falló',
                    'Lead saved but Mailchimp subscription failed',
                    $data
                );
                break;

            case SeoContentLockerEvents::LEAD_EXPIRED:
                $this->notifier->send(
                    '⛔ Lead expirado',
                    'Usuario expirado quiso iniciar sesion',
                    $data
                );
                break;

            case SeoContentLockerEvents::SAME_IP_BLOCKED:
                $this->notifier->send(
                    '🚫 IP duplicada',
                    'Multiple leads from same IP detected',
                    $data
                );
                break;

            case SeoContentLockerEvents::LEAD_RESTORED:
                $this->notifier->send(
                    '🔄 Lead restaurado',
                    'Usuario registrado restaura session',
                    $data
                );
                break;
        }
    }
}
