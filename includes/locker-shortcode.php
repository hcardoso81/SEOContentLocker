<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode [lock] para bloquear contenido
 */
function locked_content_shortcode($atts, $content = null)
{
    if (is_null($content)) return '';

    return '
    <div class="content-locked">
        ' . do_shortcode($content) . '
    </div>
    <div class="read-more-locked">
        <button class="locked-btn">Continue reading</button>
        
        <div class="trial-expired-notice">
            Free trial has expired.<br>
            If you believe this is an error, please 
            <a href="mailto:info@intermarketflow.com" target="_blank">contact the administrator</a>.
        </div>
        <div class="confirm-email-notice">
            Please confirm your email to unlock the content.
        </div>
        
        <div class="locked-separator" style="margin:10px 0; text-align:center;">
            <strong>OR</strong>
        </div>
        
        <div class="elementor-button-wrapper">
            <a class="elementor-button elementor-button-link elementor-size-sm" 
               href="https://intermarketflow.com/pricing/">
                <span class="elementor-button-content-wrapper">
                    <span class="elementor-button-text">UPGRADE</span>
                </span>
            </a>
        </div>
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
