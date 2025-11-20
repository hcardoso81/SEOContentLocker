<?php
/*
Plugin Name: SEO Content Locker
Description: Sistema SEO-friendly para ocultar contenido con cookies y captar leads.
Version: 1.0
Author: Hernan Cardoso
Author URI: https://www.linkedin.com/in/cardosohernan/
*/

if (!defined('ABSPATH')) exit;

define('SLUG', 'seo-locker');

// Archivos principales
require_once plugin_dir_path(__FILE__) . 'utils/loggers.php';
require_once plugin_dir_path(__FILE__) . 'utils/functions.php';
require_once plugin_dir_path(__FILE__) . 'utils/helper-db.php';
require_once plugin_dir_path(__FILE__) . 'utils/helper-ip.php';
require_once plugin_dir_path(__FILE__) . 'utils/helper-save-lead.php';
require_once plugin_dir_path(__FILE__) . 'utils/helper-mailchimp.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-mailchimp.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
require_once plugin_dir_path(__FILE__) . 'admin/admin-recaptcha.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-seo-locker-table.php';
require_once plugin_dir_path(__FILE__) . 'admin/modal-edit-date.php';
require_once plugin_dir_path(__FILE__) . 'admin/actions.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-ajax.php';

/**
 * ============================
 * Hook de activación para crear tablas si no existen
 * ============================
 */
register_activation_hook(__FILE__, 'seo_locker_install_tables');

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
