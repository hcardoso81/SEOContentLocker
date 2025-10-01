<?php
if (!defined('ABSPATH')) exit;

function seo_locker_shortcode($atts, $content = null) {
    ob_start();

    // Verificar cookie
    if (isset($_COOKIE['seo_locker_access'])) {
        echo '<div class="seo-locker-content">' . do_shortcode($content) . '</div>';
    } else {
        // Mostrar modal locker
        include plugin_dir_path(__FILE__) . '../templates/locker.php';
    }

    return ob_get_clean();
}
add_shortcode('lock', 'seo_locker_shortcode');
