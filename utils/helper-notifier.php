<?php
if (!defined('ABSPATH')) exit;


function seocontentlocker_dispatch_event($event, $data = [])
{
    static $dispatcher = null;

    if ($dispatcher === null) {
        $dispatcher = new SeoContentLockerDispatcher();
    }

    $dispatcher->dispatch($event, $data);
}

function seocontentlocker_should_notify($event, $email = '')
{
    // Si no hay email, no limitamos
    if (!$email) return true;

    $key = 'scl_notify_' . $event . '_' . md5($email);

    // Ya notificamos hace poco → bloquear
    if (get_transient($key)) {
        return false;
    }

    // Guardar por 5 minutos
    set_transient($key, true, 300);

    return true;
}