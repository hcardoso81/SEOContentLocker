<?php
defined('ABSPATH') || exit;
function seo_locker_render_recaptcha_settings_page() {
?>
    <div class="wrap">
        <h1>reCAPTCHA Settings</h1>
        <p>Los formularios públicos utilizan las claves específicas de reCAPTCHA v3. Las claves legacy se conservan para compatibilidad administrativa, pero no se utilizan en el flujo actual.</p>

        <?php if ( isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true' ): ?>
            <div class="notice notice-success is-dismissible">
                <p>✔ Las credenciales se guardaron correctamente en la base de datos.</p>
            </div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields('seocontentlocker_settings_group'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Legacy checkbox Site Key</th>
                    <td>
                        <input type="text"
                               name="seocontentlocker_recaptcha_site_key"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_site_key')); ?>"
                               class="regular-text"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">Legacy checkbox Secret Key</th>
                    <td>
                        <input type="text"
                               name="seocontentlocker_recaptcha_secret_key"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_secret_key')); ?>"
                               class="regular-text"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">reCAPTCHA v3 Site Key</th>
                    <td>
                        <input type="text"
                               name="seocontentlocker_recaptcha_v3_site_key"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_v3_site_key', '')); ?>"
                               class="regular-text"
                        />
                        <p class="description">Clave específica de reCAPTCHA v3. No se expone la secret key al frontend.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">reCAPTCHA v3 Secret Key</th>
                    <td>
                        <input type="password"
                               name="seocontentlocker_recaptcha_v3_secret_key"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_v3_secret_key', '')); ?>"
                               class="regular-text"
                               autocomplete="new-password"
                        />
                    </td>
                </tr>

                <tr>
                    <th scope="row">reCAPTCHA v3 Threshold</th>
                    <td>
                        <input type="number"
                               name="seocontentlocker_recaptcha_threshold"
                               value="<?php echo esc_attr(get_option('seocontentlocker_recaptcha_threshold', 0.5)); ?>"
                               min="0"
                               max="1"
                               step="0.01"
                        />
                        <p class="description">Score mínimo permitido entre 0.0 y 1.0. Valor predeterminado: 0.5.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
<?php
}
