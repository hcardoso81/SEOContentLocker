<?php
if (!defined('ABSPATH')) exit;

function seo_locker_enqueue_assets() {
    if (!is_single()) return;

    $css = plugin_dir_path(__FILE__) . '../assets/locker.css';
    if (file_exists($css)) {
        wp_enqueue_style(
            'seo-locker-css',
            plugin_dir_url(__FILE__) . '../assets/locker.css',
            [],
            filemtime($css)
        );
    }

    $js = plugin_dir_path(__FILE__) . '../assets/locker.js';
    if (file_exists($js)) {
        wp_enqueue_script(
            'seo-locker-js',
            plugin_dir_url(__FILE__) . '../assets/locker.js',
            [],
            filemtime($js),
            true
        );

        wp_localize_script('seo-locker-js', 'seo_locker_ajax', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_locker_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'seo_locker_enqueue_assets');
