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
            [],
            filemtime($js_file),
            true
        );

        // Pasamos variables al JS
        wp_localize_script('seo-locker-js', 'imf_ajax', [
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('imf_nonce'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'seo_locker_enqueue_assets');
