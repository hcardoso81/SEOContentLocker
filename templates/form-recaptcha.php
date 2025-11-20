<?php if (!defined('ABSPATH')) exit; ?>

<div class="recaptcha-wrapper">
    <div class="g-recaptcha" data-sitekey="<?= get_option('seocontentlocker_recaptcha_site_key')?>"></div>
</div>

<div id="recaptcha-error" style="display:none;" class="recaptcha-error"></div>