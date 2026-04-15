<?php
namespace SeoContentLocker\Notifier;

if (!defined('ABSPATH')) exit;

class Notifier
{
    private $to;

    public function __construct($to)
    {
        $this->to = $to;
    }

    public function send($subject, $message, $context = [])
    {
        $body = $this->formatMessage($message, $context);

        add_action('wp_mail_failed', function ($wp_error) use ($context) {
            log_error(
                $wp_error,
                'mail_failed',
                $context['email'] ?? ''
            );
        });

        $result = wp_mail(
            $this->to,
            $subject,
            $body,
            ['Content-Type: text/plain; charset=UTF-8']
        );

        if (!$result) {
            log_error(
                'wp_mail returned false',
                'mail_send',
                $context['email'] ?? ''
            );
        }
    }

    private function formatMessage($message, $context)
    {
        $lines = [
            "Message: {$message}",
            "Date: " . date('Y-m-d H:i:s'),
        ];

        foreach ($context as $key => $value) {
            $lines[] = strtoupper($key) . ": " . print_r($value, true);
        }

        return implode("\n", $lines);
    }
}
