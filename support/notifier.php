<?php
if (!defined('ABSPATH')) exit;

function seocontentlocker_dispatch_event($event, $data = [])
{
    static $dispatcher = null;

    if ($dispatcher === null) {
        $dispatcher = new \SeoContentLocker\Notifier\Dispatcher();
    }

    $dispatcher->dispatch($event, $data);
}

function seocontentlocker_should_notify($event, $email = '')
{
    if (!$email) return true;

    $key = 'scl_notify_' . $event . '_' . md5($email);

    if (get_transient($key)) {
        return false;
    }

    set_transient($key, true, 300);

    return true;
}
