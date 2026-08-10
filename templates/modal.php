<?php
if (!defined('ABSPATH')) exit;
?>

<div id="lead-overlay" style="display:none;">
  <div class="overlay-backdrop"></div>
  <div class="overlay-modal">
    <div class="modal-header">
      <button class="modal-close" type="button" aria-label="<?php esc_attr_e('Close modal', 'seo-locker'); ?>">&times;</button>
    </div>

    <div class="locker-public-shell locker-public-shell-modal">
      <h2>Unlock Full Access</h2>

      <div class="locker-copy">
        <p>We create professional content for traders, based on intermarket, macro, technical, quant, and flow analysis.</p>
        <p>Welcome aboard — enjoy the ride.</p>
      </div>

      <p class="trial-note">
        If you have already registered before, please enter your email again to recover your session.
      </p>

      <form id="lead-capture-form" class="locker-public-form" data-recaptcha-required="1" data-recaptcha-action="locker_modal_submit" novalidate>
        <?php
        locker_component('form-antibot', [
          'form_type' => \SeoContentLocker\Services\AntiBotProtectionService::FORM_MODAL,
        ]);
        locker_component('form-email');
        locker_component('form-consent');
        locker_component('form-recaptcha');
        locker_component('button-submit');
        ?>
      </form>
    </div>
  </div>
</div>

<?php locker_component('check-loader'); ?>
