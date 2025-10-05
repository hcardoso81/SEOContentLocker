<?php
if (!defined('ABSPATH')) exit;

function seo_locker_enqueue_assets() {
    if (!is_single()) return; // Solo en posts

    $js_file  = plugin_dir_path(__DIR__) . 'assets/locker.js';
    $css_file = plugin_dir_path(__DIR__) . 'assets/locker.css';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'seo-locker-css',
            plugin_dir_url(__DIR__) . 'assets/locker.css',
            [],
            filemtime($css_file)
        );
    }

    if (file_exists($js_file)) {
        wp_enqueue_script(
            'seo-locker-js',
            plugin_dir_url(__DIR__) . 'assets/locker.js',
            ['jquery'], // opcional: depende si usás $
            filemtime($js_file),
            true
        );

        // Pasamos variables al JS con prefijo consistente
        wp_localize_script('seo-locker-js', 'seocontentlocker_ajax', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seocontentlocker_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'seo_locker_enqueue_assets');



function seocontentlocker_mailchimp_admin_scripts($hook_suffix)
{
    // Solo en admin
    if (!is_admin()) return;

    // Solo en la página de ajustes de Mailchimp
    if ($hook_suffix !== 'seo-locker_page_seocontentlocker-mailchimp') return;

    $plugin_root = dirname(__DIR__); // un nivel arriba de includes

    $js_file  = $plugin_root . '/assets/locker-mailchimp.js';
    $js_url   = plugins_url('../assets/locker-mailchimp.js', __FILE__);
    $css_file = $plugin_root . '/assets/mailchimp-admin.css';
    $css_url  = plugins_url('../assets/mailchimp-admin.css', __FILE__);

    if (file_exists($js_file)) {
        wp_enqueue_script(
            'seocontentlocker-mailchimp-admin',
            $js_url,
            ['jquery'],
            filemtime($js_file),
            true
        );
    }

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'seocontentlocker-mailchimp-admin',
            $css_url,
            [],
            filemtime($css_file)
        );
    }

    // Pasar variables JS
    wp_localize_script('seocontentlocker-mailchimp-admin', 'seocontentlocker_mailchimp', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('seocontentlocker_mailchimp_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'seocontentlocker_mailchimp_admin_scripts');


add_action('admin_enqueue_scripts', 'seo_locker_admin_custom_styles');
function seo_locker_admin_custom_styles($hook_suffix) {
    if ($hook_suffix !== 'toplevel_page_seo-locker') return; // Solo en tu menú principal

    $custom_css = '
        .toplevel_page_seo-locker table.widefat th:nth-child(1),
        .toplevel_page_seo-locker table.widefat td:nth-child(1) {
            width: 50px;
        }
        .toplevel_page_seo-locker table.widefat th:nth-child(2),
        .toplevel_page_seo-locker table.widefat td:nth-child(2) {
            width: 300px;
        }
    ';
    wp_add_inline_style('wp-admin', $custom_css);
}
