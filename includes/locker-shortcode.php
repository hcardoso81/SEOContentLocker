<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode [lock] para bloquear contenido
 */
function locked_content_shortcode($atts, $content = null)
{
    if (is_null($content)) return '';

    return '<div class="content-locked">'
        . do_shortcode($content) .
        '</div>
        <div class="read-more-locked">
            <button class="locked-btn">Continue reading</button>
            <span class="trial-expired-notice">
                Free trial has expired. Contact <a href="mailto:info@intermarketflow.com" target="_blank">administrator</a>
            </span>
        </div>';
}
add_shortcode('lock', 'locked_content_shortcode');

/**
 * Inyectar el overlay del locker automáticamente al final del contenido de los posts
 */
function seo_locker_inject_overlay($content)
{
    if (!is_single()) return $content; // solo posts individuales

    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/locker.php';
    $overlay_html = ob_get_clean();

    return $content . $overlay_html;
}
add_filter('the_content', 'seo_locker_inject_overlay');
