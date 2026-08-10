<?php
/*
Plugin Name: SEO Content Locker
Description: Sistema SEO-friendly para bloquear contenido y capturar leads mediante First Name y email. Incluye restauración de acceso, protección anti-spam, reCAPTCHA, Mailchimp, notificaciones administrativas y reporte diario de leads con 13 días de antigüedad mediante cron del servidor.
Version: 1.1.21
Author: Hernan Cardoso
Author URI: https://www.linkedin.com/in/cardosohernan/
*/

if (!defined('ABSPATH')) exit;

define('SLUG', 'seo-locker');
define('SEO_CONTENT_LOCKER_VERSION', '1.1.21');
define('LOCKER_REPORT_EMAIL', "martingalachedetoro@gmail.com");
define('SEO_CONTENT_LOCKER_REPORT_TIMEZONE', 'America/Argentina/Buenos_Aires');
define('SEO_CONTENT_LOCKER_THANK_YOU_PATH', '/your-intermarketflow-access-is-confirmed/');
define('SEO_CONTENT_LOCKER_LANDING_THANK_YOU_PATH', '/thank-you/');


// Archivos principales
require_once plugin_dir_path(__FILE__) . 'autoload.php';
require_once plugin_dir_path(__FILE__) . 'support/loggers.php';
require_once plugin_dir_path(__FILE__) . 'support/functions.php';
require_once plugin_dir_path(__FILE__) . 'support/ip.php';
require_once plugin_dir_path(__FILE__) . 'support/notifier.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-mailchimp.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-recaptcha.php';
require_once plugin_dir_path(__FILE__) . 'admin/modal-edit-date.php';
require_once plugin_dir_path(__FILE__) . 'admin/actions.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-ajax.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-cron.php';


/**
 * ============================
 * Hook de activación para crear tablas si no existen
 * ============================
 */
register_activation_hook(__FILE__, 'seo_locker_install_tables');
register_activation_hook(__FILE__, 'seocontentlocker_schedule_day_13_report');
register_deactivation_hook(__FILE__, 'seocontentlocker_unschedule_day_13_report');

add_action('plugins_loaded', 'seo_locker_maybe_upgrade_tables');
add_action('plugins_loaded', 'seocontentlocker_schedule_day_13_report');

function seo_locker_maybe_upgrade_tables() {
    $db_version = get_option('seo_content_locker_db_version', '0');

    if (version_compare($db_version, '1.1.3', '>=')) {
        return;
    }

    seo_locker_install_tables();
    update_option('seo_content_locker_db_version', '1.1.3');
}

function seo_locker_install_tables() {
    seo_locker_install();
    seo_locker_create_table_same_IP();
}

/**
 * ============================
 * Crear tabla principal de leads
 * ============================
 */
function seo_locker_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(100) NOT NULL DEFAULT '',
        email VARCHAR(255) NOT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        post_slug VARCHAR(255) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        plan VARCHAR(20) DEFAULT 'free',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        token VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * ============================
 * Crear tabla para IPs duplicadas
 * ============================
 */
function seo_locker_create_table_same_IP() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions_same_ip';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        country VARCHAR(100) DEFAULT NULL,
        post_slug VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
