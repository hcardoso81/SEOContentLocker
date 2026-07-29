<?php if (!defined('ABSPATH')) exit; ?>

<input type="text" id="lead-first-name"
       name="first_name"
       placeholder="<?php echo esc_attr__('Your first name', 'seo-locker') . (!empty($show_required_markers) ? ' *' : ''); ?>"
       autocomplete="given-name"
       required />

<input type="email" id="lead-email"
       name="email"
       placeholder="<?php echo esc_attr__('Your email', 'seo-locker') . (!empty($show_required_markers) ? ' *' : ''); ?>"
       autocomplete="email"
       required />
