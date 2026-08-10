<?php if (!defined('ABSPATH')) exit; ?>

<div class="locker-honeypot" aria-hidden="true">
    <label for="locker-website-<?php echo esc_attr($form_type); ?>">Leave this field empty</label>
    <input type="text"
           id="locker-website-<?php echo esc_attr($form_type); ?>"
           name="locker_website"
           value=""
           tabindex="-1"
           autocomplete="off"
           aria-hidden="true" />
</div>

<input type="hidden"
       name="locker_form_token"
       value="<?php echo esc_attr((new \SeoContentLocker\Services\AntiBotProtectionService())->createFormToken($form_type, !empty($is_landing))); ?>" />
