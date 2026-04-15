<?php if (!defined('ABSPATH')) exit; ?>
<label class="locker-field">
    <span class="locker-field-label"><?php esc_html_e('Email address', 'seo-locker'); ?></span>
    <input type="email" id="lead-email"
        placeholder="<?php esc_attr_e('name@example.com', 'seo-locker'); ?>"
        autocomplete="email"
        required />
</label>
