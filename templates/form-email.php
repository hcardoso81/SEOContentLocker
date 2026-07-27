<?php if (!defined('ABSPATH')) exit; ?>
<input type="text" id="lead-first-name"
       name="first_name"
       placeholder="<?php esc_attr_e('Your first name', 'seo-locker'); ?>"
       autocomplete="given-name"
       required />
<input type="email" id="lead-email"
       name="email"
       placeholder="<?php esc_attr_e('Your email', 'seo-locker'); ?>"
       autocomplete="email"
       required />
