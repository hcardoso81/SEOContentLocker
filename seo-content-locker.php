<?php
/*
Plugin Name: SEO Content Locker
Description: Sistema SEO-friendly para ocultar contenido con cookies y captar leads.
Version: 1.0
Author: Hernan Cardoso
Author URI: https://www.linkedin.com/in/cardosohernan/
*/

if (!defined('ABSPATH')) exit;

// Archivos principales
require_once plugin_dir_path(__FILE__) . 'includes/locker-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-shortcode.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/locker-ajax.php';

// Crear tabla al activar
register_activation_hook(__FILE__, 'seo_locker_install');

function seo_locker_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'leads_subscriptions';

    $charset_collate = $wpdb->get_charset_collate();

    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ip varchar(45) DEFAULT NULL,
        country varchar(100) DEFAULT NULL,
        post_slug VARCHAR(255) NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY unique_email (email)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
