<div id="lead-overlay" style="display:none;">
  <div class="overlay-backdrop"></div>
  <div class="overlay-modal">
    <div class="modal-header">
      <button class="modal-close">&times;</button>
    </div>

    <h2><?php esc_html_e('Unlock Full Access', 'seo-locker'); ?></h2>
    <p><?php esc_html_e('Subscribe with your email to read the complete article and gain full access:', 'seo-locker'); ?></p>
    <p class="trial-note">
      <?php esc_html_e('Your subscription also starts a free 40-day trial, giving you unlimited access to all premium content.', 'seo-locker'); ?>
    </p>

    <input type="email" id="lead-email" placeholder="<?php esc_attr_e('Your email', 'seo-locker'); ?>" required />

    <label class="consent-text">
      <input type="checkbox" id="lead-consent" />
      <span>
        I agree to the <a href="/terms-of-service" target="_blank">Terms and Conditions</a>,
        the <a href="/privacy-policy" target="_blank">Privacy Policy</a>,
        and to receive related communications.
      </span>
    </label>

    <button id="lead-submit" disabled>
      <?php esc_html_e('Continue', 'seo-locker'); ?>
    </button>
  </div>
</div>

<div id="subscription-toast" class="toast hidden">
  <span class="toast-message">Subscription successful!</span>
  <button class="toast-close">&times;</button>
</div>