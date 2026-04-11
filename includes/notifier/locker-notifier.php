<?php
class SeoContentLockerNotifier
{
    private $to;

    public function __construct($to)
    {
        $this->to = $to;
    }

    public function send($subject, $message, $context = [])
    {
        $body = $this->formatMessage($message, $context);

        mail(
            $this->to,
            $subject,
            $body,
            ['Content-Type: text/plain; charset=UTF-8']
        );
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