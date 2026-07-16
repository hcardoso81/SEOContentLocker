<?php
namespace SeoContentLocker\Notifier;

if (!defined('ABSPATH')) exit;

class Events
{
    const LEAD_CREATED_SUCCESS = 'lead_created_success';
    const MAILCHIMP_FAILED = 'mailchimp_failed';
    const LEAD_RESTORED = 'lead_restored';
    const LEAD_EXPIRED = 'lead_expired';
    const SAME_IP_BLOCKED = 'same_ip_blocked';
    const LEAD_RESTORED_DIFFERENT_IP = 'lead_restored_different_ip';
}
