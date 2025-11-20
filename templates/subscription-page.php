<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-form-wrapper">

    <?php
    locker_component('notice-confirm');
    locker_component('notice-expired');
    ?>

    <h2>Subscribe to Our Updates!</h2>

    <p class="subscription-text">
        Enter your email and agree to the terms to get full access to our updates.
    </p>

    <form id="my-subscription-form-page">
        <?php

        locker_component('form-email');
        locker_component('form-consent');
        locker_component('form-recaptcha');
        locker_component('button-submit');

        ?>
    </form>
</div>