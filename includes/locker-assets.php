<?php
if (!defined('ABSPATH')) exit;

/**
 * Cargar assets del front-end (posts/páginas)
 */
function seo_locker_frontend_assets()
{
    if (!is_singular()) return;

    $plugin_dir  = plugin_dir_path(__DIR__) . 'assets/';
    $plugin_url  = plugin_dir_url(__DIR__) . 'assets/';

    $css_file = $plugin_dir . 'locker.css';

    $js_file_front  = $plugin_dir . 'front.js';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'seo-locker-css',
            $plugin_url . 'locker.css',
            [],
            filemtime($css_file)
        );
    }


    if (file_exists($js_file_front)) {
        wp_enqueue_script(
            'seocontentlocker-front',
            $plugin_url . 'front.js',
            ['jquery'],
            filemtime($js_file_front),
            true
        );
    }


    wp_localize_script('seocontentlocker-front', 'seocontentlocker_ajax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('seocontentlocker_nonce'),
    ]);

    wp_enqueue_script(
        'google-recaptcha',
        'https://www.google.com/recaptcha/api.js',
        [],
        null,
        true
    );
}

add_action('wp_enqueue_scripts', 'seo_locker_frontend_assets');


/**
 * Cargar assets del admin
 */
function seo_locker_admin_assets($hook_suffix)
{
    $plugin_dir  = plugin_dir_path(__DIR__) . 'assets/';
    $plugin_url  = plugin_dir_url(__DIR__) . 'assets/';

    if (
        $hook_suffix !== 'toplevel_page_' . SLUG &&
        $hook_suffix !== 'seo-content-locker_page_seocontentlocker-mailchimp'
    ) return;

    $css_file = $plugin_dir . 'admin-locker.css';
    $js_file  = $plugin_dir . 'admin-locker.js';

    if (file_exists($css_file)) {
        wp_enqueue_style('seo-locker-admin-css', $plugin_url . 'admin-locker.css', [], filemtime($css_file));
    }

    if (file_exists($js_file)) {
        wp_enqueue_script('seo-locker-admin-js', $plugin_url . 'admin-locker.js', ['jquery'], filemtime($js_file), true);
    }

    wp_localize_script('seo-locker-admin-js', 'seocontentlocker_mailchimp', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('seocontentlocker_mailchimp_test')
    ]);

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css', [], '1.12.1');

    $inline_js = <<<JS
jQuery(function($){
    function initSeoLockerDatepicker() {
        var \$input = $("#new-expire-date");
        if (!\$input.length || \$input.data('seoDateInit')) return;
        \$input.datepicker({
            dateFormat: "dd/mm/yy",
            changeMonth: true,
            changeYear: true,
            yearRange: "c-10:c+10",
            showButtonPanel: true
        });
        \$input.data('seoDateInit', true);
    }
    initSeoLockerDatepicker();
    $(document).on('click', '[onclick*="openEditModal"]', function(){ 
        setTimeout(initSeoLockerDatepicker, 10); 
    });
});
JS;

    wp_add_inline_script('jquery-ui-datepicker', $inline_js);
}
add_action('admin_enqueue_scripts', 'seo_locker_admin_assets');
