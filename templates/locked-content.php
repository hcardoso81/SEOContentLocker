<?php if (!defined('ABSPATH')) exit; ?>

<div class="content-locked">
    <?php echo $locked_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>

<div class="read-more-locked">
    <button type="button" class="locked-btn" data-locker-open>
        <?php esc_html_e('Continue Reading', 'seo-locker'); ?>
    </button>

    <div class="locked-separator">
        <strong><?php esc_html_e('OR', 'seo-locker'); ?></strong>
    </div>

    <a href="https://intermarketflow.com/pricing/" class="locked-btn">
        <?php esc_html_e('Upgrade', 'seo-locker'); ?>
    </a>
</div>

<div class="trial-expired-notice">
    <div class="trial-expired">
        <?php esc_html_e('Free trial has expired.', 'seo-locker'); ?><br>
        <?php esc_html_e('If you believe this is an error, please', 'seo-locker'); ?>
        <a href="mailto:contact@intermarketflow.com" target="_blank" rel="noopener noreferrer">
            <?php esc_html_e('contact the administrator', 'seo-locker'); ?>
        </a>.
    </div>

    <div class="locked-separator">
        <strong><?php esc_html_e('OR', 'seo-locker'); ?></strong>
    </div>

    <a href="https://intermarketflow.com/pricing/" class="locked-btn">
        <?php esc_html_e('Upgrade', 'seo-locker'); ?>
    </a>
</div>
