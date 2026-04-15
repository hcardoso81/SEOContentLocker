<?php
if (!defined('ABSPATH')) exit;
?>

<div id="lead-overlay" style="display:none;">
  <div class="overlay-backdrop"></div>
  <div class="overlay-modal">
    <button class="modal-close" aria-label="<?php esc_attr_e('Close dialog', 'seo-locker'); ?>">&times;</button>

    <div class="locker-shell">
      <div class="locker-editorial">
        <span class="locker-kicker">Members Edition</span>
        <h2>Unlock the full brief before the market moves.</h2>
        <p class="locker-lead">
          Access premium research built around macro context, intermarket signals, technical structure, quantitative clues, and flow-based execution.
        </p>

        <div class="locker-value-grid">
          <div class="locker-value-card">
            <strong>Weekly depth</strong>
            <span>Actionable reads designed for serious traders, not casual headlines.</span>
          </div>
          <div class="locker-value-card">
            <strong>Clear framework</strong>
            <span>Context first, narrative second, with a disciplined process behind every note.</span>
          </div>
          <div class="locker-value-card">
            <strong>Fast recovery</strong>
            <span>If you already registered, re-enter your email and your access will be restored.</span>
          </div>
        </div>
      </div>

      <div class="locker-panel">
        <span class="locker-panel-label">Complimentary Access</span>
        <h3>Continue reading with your email</h3>
        <p class="locker-panel-copy">
          Join the list to unlock the complete article and receive future updates with the same editorial standard.
        </p>

        <div class="locker-inline-message" aria-live="polite"></div>

        <div class="locker-form-stack">
          <?php
          locker_component('form-email');
          locker_component('form-consent');
          locker_component('form-recaptcha');
          locker_component('button-submit');
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php locker_component('check-loader'); ?>
