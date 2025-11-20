<?php
if (!defined('ABSPATH')) exit;
?>

<div id="lead-overlay" style="display:none;">
  <div class="overlay-backdrop"></div>
  <div class="overlay-modal">
    <div class="modal-header">
      <button class="modal-close">&times;</button>
    </div>

    <h2>Unlock Full Access</h2>

    <p>We create professional content for traders, based on intermarket, macro, technical, quant, and flow analysis.</p>
    <p>Welcome aboard — enjoy the ride.</p>

    <p class="trial-note">
      If you have already registered before, please enter your email again to recover your session.
    </p>
    <?php
    locker_component('form-email');
    locker_component('form-consent');
    locker_component('form-recaptcha');
    locker_component('button-submit');
    ?>
  </div>
</div>
<?php 
  locker_component('check-loader');
?>