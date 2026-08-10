<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-form-wrapper">
    <div class="locker-public-shell locker-public-shell-page">
        <?php
        locker_component('notice-confirm');
        locker_component('notice-expired');
        ?>

        <h2>Subscribe to Our Updates!</h2>

        <p class="subscription-text">
            Enter your email to get full access to our updates.
        </p>

        <form id="my-subscription-form-page" class="locker-public-form locker-public-form-simple" data-landing="<?php echo $is_landing ? '1' : '0'; ?>" data-recaptcha-required="1" data-recaptcha-action="subscription_simple_submit" novalidate>
            <?php
            locker_component('form-antibot', [
                'form_type' => \SeoContentLocker\Services\AntiBotProtectionService::FORM_SIMPLE,
                'is_landing' => $is_landing,
            ]);
            locker_component('form-email', [
                'show_required_markers' => true,
            ]);
            locker_component('form-recaptcha');
            locker_component('button-submit', [
                'submit_label' => __('ACCESS RESEARCH', 'seo-locker'),
            ]);
            ?>
        </form>
    </div>
</div>
