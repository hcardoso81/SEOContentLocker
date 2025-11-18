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
        <button id="locked-btn" class="locked-btn">Continue Reading</button>
        <div class="locked-separator" style="margin:10px 0; text-align:center;">
            <strong>OR</strong>
        </div>
        <a href="https://intermarketflow.com/pricing/" class="locked-btn">UPGRADE</a>
    </div>
     <div class="trial-expired-notice">
        <div class="trial-expired">
            Free trial has expired.<br>
            If you believe this is an error, please 
            <a href="mailto:contact@intermarketflow.com" target="_blank">contact the administrator</a>.
        </div>
        <div class="locked-separator" style="margin:10px 0; text-align:center;">
                <strong>OR</strong>
        </div>
            <a href="https://intermarketflow.com/pricing/" class="locked-btn">UPGRADE</a>
        </div>
    </div>';
}
add_shortcode('lock', 'locked_content_shortcode');

/**
 * Inyectar el overlay del locker automáticamente al final del contenido de los posts
 */
function seo_locker_inject_overlay($content)
{
    if (!is_single()) return $content;

    ob_start();
    include plugin_dir_path(__FILE__) . '../templates/modal.php';
    $overlay_html = ob_get_clean();

    return $content . $overlay_html;
}
add_filter('the_content', 'seo_locker_inject_overlay');


function my_subscription_form_shortcode()
{
    ob_start();
    locker_component('subscription-page');
    return ob_get_clean();
}
add_shortcode('my_subscription_form', 'my_subscription_form_shortcode');
