<?php
defined('ABSPATH') || exit;
function seo_locker_render_recaptcha_settings_page() {
?>
    <div class="wrap">
        <h1>reCAPTCHA Settings</h1>

        <?php if ( isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true' ): ?>
            <div class="notice notice-success is-dismissible">
                <p>✔ Las credenciales se guardaron correctamente en la base de datos.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('seocontentlocker_settings_group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Site Key</th>
                    <td>
                        <input type="text"
                               name="seocontentlocker_recaptcha_site_key"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_site_key')); ?>"
                               class="regular-text"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">Secret Key</th>
                    <td>
                        <input type="text"
                               name="seocontentlocker_recaptcha_secret_key"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_secret_key')); ?>"
                               class="regular-text"
                        />
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
<?php
}
