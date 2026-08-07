<?php
if (!defined('ABSPATH')) exit;

function scl_write_log($filename, $data)
{
    $path = seocontentlocker_get_log_path($filename);
    $line = '[' . wp_date('Y-m-d H:i:s') . '] ' . print_r($data, true) . PHP_EOL;
    file_put_contents($path, $line, FILE_APPEND);
}

function seocontentlocker_get_log_path($filename)
{
    $filename = basename($filename);
    $base = trailingslashit(plugin_dir_path(dirname(__FILE__))) . 'logs/';

    if (!file_exists($base)) {
        wp_mkdir_p($base);
    }

    return $base . $filename;
}

function log_expires($email = '', $slug = '')
{
    scl_write_log('expired.log', [
        'email' => $email,
        'slug' => $slug,
    ]);
}

function log_error($error, $context = '', $email = '')
{
    scl_write_log('error.log', [
        'context' => $context,
        'email' => $email,
        'error' => $error,
    ]);
}

function log_suscription($email = '', $ip = '', $country = '')
{
    scl_write_log('subscription.log', [
        'email' => $email,
        'ip' => $ip,
        'country' => $country,
    ]);
}

function log_restore($email, $slug = '')
{
    scl_write_log('restore.log', [
        'email' => $email,
        'slug' => $slug,
    ]);
}

function log_access($email, $slug)
{
    scl_write_log('access.log', [
        'email' => $email,
        'slug' => $slug,
    ]);
}

function log_same_ip($ip = '', $country = '', $email_old = '', $email_new = '', $slug = '')
{
    scl_write_log('same-ip.log', [
        'ip' => $ip,
        'country' => $country,
        'email_old' => $email_old,
        'email_new' => $email_new,
        'slug' => $slug,
    ]);
}

function log_mailchimp_success($email = '', $status = '', $code = 200)
{
    scl_write_log('mailchimp-success.log', [
        'email' => $email,
        'status' => $status,
        'code' => $code,
    ]);
}
