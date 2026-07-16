<?php
namespace SeoContentLocker\Notifier;

if (!defined('ABSPATH')) exit;

class Dispatcher
{
    private $notifier;

    public function __construct()
    {
        $email = LOCKER_REPORT_EMAIL;

        if (!$email) {
            $this->notifier = null;
            return;
        }

        $this->notifier = new Notifier($email);
    }

    public function dispatch($event, $data = [])
    {
        if (!$this->notifier) {
            return;
        }

        $email = $data['email'] ?? '';

        if (!seocontentlocker_should_notify($event, $email)) {
            return;
        }

        switch ($event) {
            case Events::LEAD_CREATED_SUCCESS:
                $this->notifier->send(
                    '✅ Lead creado + Mailchimp OK',
                    'Nuevo Lead ',
                    $data
                );
                break;

            case Events::MAILCHIMP_FAILED:
                $this->notifier->send(
                    '⚠️ Mailchimp falló',
                    'Lead saved but Mailchimp subscription failed',
                    $data
                );
                break;

            case Events::LEAD_EXPIRED:
                $this->notifier->send(
                    '⛔ Lead expirado',
                    'Usuario expirado quiso iniciar sesion',
                    $data
                );
                break;

            case Events::SAME_IP_BLOCKED:
                $this->notifier->send(
                    '🚫 IP duplicada',
                    'A different email attempted to register from an IP already assigned to another lead',
                    $data
                );
                break;

            case Events::LEAD_RESTORED_DIFFERENT_IP:
                $this->notifier->send(
                    'Valid lead from another IP',
                    'An existing lead restored access from a different IP address',
                    $data
                );
                break;

            case Events::LEAD_RESTORED:
                $this->notifier->send(
                    '🔄 Lead restaurado',
                    'Usuario registrado restaura session',
                    $data
                );
                break;
        }
    }
}
