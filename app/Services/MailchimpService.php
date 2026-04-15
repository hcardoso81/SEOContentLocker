<?php
if (!defined('ABSPATH')) exit;

class MailchimpService
{
    public function subscribe($email, $slug)
    {
        return seocontentlocker_mailchimp_subscribe($email, $slug);
    }
}
