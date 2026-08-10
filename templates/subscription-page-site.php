<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-form-wrapper">
    <div class="locker-public-shell locker-public-shell-page">
        <?php
        locker_component('notice-confirm');
        locker_component('notice-expired');
        ?>

        <h2>Subscribe to Our Updates!</h2>

        <p class="subscription-text">
            Enter your email and agree to the terms to get full access to our updates.
        </p>

        <form id="my-subscription-form-site" class="locker-public-form" data-recaptcha-required="1" data-recaptcha-action="subscription_site_submit" novalidate>
            <?php
            locker_component('form-antibot', [
                'form_type' => \SeoContentLocker\Services\AntiBotProtectionService::FORM_SITE,
            ]);
            locker_component('form-email');
            locker_component('form-consent');
            locker_component('form-recaptcha');
            locker_component('button-submit', [
                'submit_label' => __('ACCESS RESEARCH', 'seo-locker'),
            ]);
            ?>
        </form>
    </div>
</div>
