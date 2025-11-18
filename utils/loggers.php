<?php 
if (!defined('ABSPATH')) exit;

/**
 * Log expired trial or access attempts.
 *
 * Stores a JSON entry with date and email into:
 *   wp-content/seocontentlocker-expired.log
 *
 * @param string $email The email associated with the expired attempt.
 * @return void
 */
function log_expires($email = '')
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-expired.log';

    $entry = json_encode([
        'date'  => date('Y-m-d H:i:s'),
        'email' => $email
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}


/**
 * Log plugin errors for SEO Content Locker.
 *
 * Writes detailed error information to:
 *   wp-content/seocontentlocker-errors.log
 *
 * Useful for debugging issues related to API calls,
 * unexpected values, failed processes, etc.
 *
 * @param string|Exception $error   The error message or Exception instance.
 * @param string           $context Optional context describing where the error occurred.
 * @param string           $email   Optional email related to the error.
 * @return void
 */
function log_error($error, $context = '', $email = '')
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-errors.log';

    $entry = json_encode([
        'date'    => date('Y-m-d H:i:s'),
        'context' => $context,
        'error'   => ($error instanceof Exception)
                        ? $error->getMessage()
                        : (string) $error,
        'email'   => $email
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}


/**
 * Log successful lead subscriptions from SEO Content Locker.
 *
 * Stores lead information into:
 *   wp-content/seocontentlocker-suscription.log
 *
 * This is helpful to track sign-ups, incoming traffic sources,
 * and geographical metadata.
 *
 * @param string $email   User's email address.
 * @param string $ip      User's IP address.
 * @param string $country ISO country code or resolved country.
 * @return void
 */
function log_suscription($email = '', $ip = '', $country = '')
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-suscription.log';

    $entry = json_encode([
        'date'    => date('Y-m-d H:i:s'),
        'email'   => $email,
        'ip'      => $ip,
        'country' => $country
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}


/**
 * Log restored access events (e.g., manually restored unlocked content).
 *
 * Stored in:
 *   wp-content/seocontentlocker-restore.log
 *
 * Useful when an admin manually restores access or a user regains access.
 *
 * @param string $email The email for which access was restored.
 * @return void
 */
function log_restore($email)
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-restore.log';

    $entry = json_encode([
        'date'  => date('Y-m-d H:i:s'),
        'email' => $email
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}

function log_access($email, $slug)
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-access.log';

    $entry = json_encode([
        'date'    => date('Y-m-d H:i:s'),
        'email'    => $email,
        'slug'    => $slug
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}


function log_same_ip($ip = '', $email_old = '', $email_new = '')
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-same-ip.log';

    $entry = json_encode([
        'date'    => date('Y-m-d H:i:s'),
        'ip'      => $ip,
        'email_old'   => $email_old,
        'email_new'   => $email_new
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}

/**
 * Log successful Mailchimp subscriptions.
 *
 * Saves records to:
 *   wp-content/seocontentlocker-mailchimp-success.log
 *
 * Useful to audit which users fueron registrados correctamente en Mailchimp,
 * diferenciarlo del log interno de suscripciones locales.
 *
 * @param string $email  The subscribed user's email.
 * @param string $status Returned Mailchimp status ("subscribed", etc).
 * @param int    $code   HTTP response code from Mailchimp API.
 * @return void
 */
function log_mailchimp_success($email = '', $status = '', $code = 200)
{
    $log_file = WP_CONTENT_DIR . '/seocontentlocker-mailchimp-success.log';

    $entry = json_encode([
        'date'   => date('Y-m-d H:i:s'),
        'email'  => $email,
        'status' => $status,
        'code'   => $code
    ]) . PHP_EOL;

    file_put_contents($log_file, $entry, FILE_APPEND);
}