<?php
if (!defined('ABSPATH')) exit;

class SeoContentLockerDispatcher
{
    private $notifier;

    public function __construct()
    {
        $email = get_option('seocontentlocker_report_email');

        if (!$email) {
            $this->notifier = null;
            return;
        }

        $this->notifier = new SeoContentLockerNotifier($email);
    }

    public function dispatch($event, $data = [])
    {
        // 🔕 feature toggle
        if (!get_option('seocontentlocker_enable_notifications')) {
            return;
        }

        if (!$this->notifier) return;

        switch ($event) {

            case SeoContentLockerEvents::LEAD_CREATED_SUCCESS:
                $this->notifier->send(
                    '✅ Lead creado + Mailchimp OK',
                    'Lead successfully created and synced',
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
                    'Expired lead tried to access',
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
        }
    }
}