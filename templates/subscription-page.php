<?php if (!defined('ABSPATH')) exit; ?>

<section class="subscription-page-shell">
    <div class="subscription-page-copy">
        <span class="subscription-kicker">Editorial Access</span>
        <h2>Stay ahead of the narrative, not behind it.</h2>
        <p class="subscription-intro">
            Subscribe to receive concise, high-signal market commentary shaped by macro, cross-asset behavior, technical context, quantitative structure, and flow.
        </p>

        <div class="subscription-points">
            <div class="subscription-point">
                <strong>Signal over noise</strong>
                <span>Research designed to help serious readers orient quickly and act with more context.</span>
            </div>
            <div class="subscription-point">
                <strong>Consistent cadence</strong>
                <span>A direct line to new updates, with the same tone and rigor as the locked content.</span>
            </div>
            <div class="subscription-point">
                <strong>Simple access</strong>
                <span>Already subscribed before? Use the same email and the system will recover your access.</span>
            </div>
        </div>
    </div>

    <div class="subscription-form-wrapper">
        <?php
        locker_component('notice-confirm');
        locker_component('notice-expired');
        ?>

        <div class="subscription-card-header">
            <span class="subscription-card-label">Join the newsletter</span>
            <h3>Get full access and future updates</h3>
            <p class="subscription-text">
                Enter your email, confirm consent, and continue into the full research experience.
            </p>
        </div>

        <div class="locker-inline-message" aria-live="polite"></div>

        <form id="my-subscription-form-page" class="subscription-form-card">
            <?php
            locker_component('form-email');
            locker_component('form-consent');
            locker_component('form-recaptcha');
            locker_component('button-submit');
            ?>
        </form>
    </div>
</section>
