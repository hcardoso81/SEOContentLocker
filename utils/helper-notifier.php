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