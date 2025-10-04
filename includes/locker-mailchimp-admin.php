<?php
if (!defined('ABSPATH')) exit;

/**
 * Mailchimp settings page for SEO Content Locker (Etapa 1)
 * Guarda:
 *  - seocontentlocker_mc_api_key
 *  - seocontentlocker_mc_account
 *  - seocontentlocker_mc_list_id
 */

/* === Añadir submenu bajo SEO Locker === */
add_action('admin_menu', 'seocontentlocker_add_mailchimp_submenu', 20);
function seocontentlocker_add_mailchimp_submenu() {
    add_submenu_page(
        'seo-locker', // slug del menú principal creado en locker-admin.php
        'Mailchimp Integration',
        'Mailchimp',
        'manage_options',
        'seocontentlocker-mailchimp',
        'seocontentlocker_mailchimp_page_callback'
    );
}

/* === Render de la página y guardado === */
function seocontentlocker_mailchimp_page_callback() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos suficientes.'));
    }

    // Guardado
    if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['seocontentlocker_mailchimp_nonce'])) {
        if (!wp_verify_nonce($_POST['seocontentlocker_mailchimp_nonce'], 'seocontentlocker_save_mailchimp')) {
            echo '<div class="notice notice-error is-dismissible"><p>Nonce inválido.</p></div>';
        } else {
            $api_key = isset($_POST['mc_api_key']) ? sanitize_text_field(wp_unslash($_POST['mc_api_key'])) : '';
            $account = isset($_POST['mc_account']) ? sanitize_text_field(wp_unslash($_POST['mc_account'])) : '';
            $list_id = isset($_POST['mc_list_id']) ? sanitize_text_field(wp_unslash($_POST['mc_list_id'])) : '';

            update_option('seocontentlocker_mc_api_key', $api_key);
            update_option('seocontentlocker_mc_account', $account);
            update_option('seocontentlocker_mc_list_id', $list_id);

            echo '<div class="notice notice-success is-dismissible"><p>Ajustes de Mailchimp guardados correctamente.</p></div>';
        }
    }

    $api_key = esc_attr(get_option('seocontentlocker_mc_api_key', ''));
    $account = esc_attr(get_option('seocontentlocker_mc_account', ''));
    $list_id = esc_attr(get_option('seocontentlocker_mc_list_id', ''));

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Mailchimp — SEO Content Locker', 'seocontentlocker'); ?></h1>

        <form method="post" action="">
            <?php wp_nonce_field('seocontentlocker_save_mailchimp', 'seocontentlocker_mailchimp_nonce'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="mc_api_key"><?php esc_html_e('API Key', 'seocontentlocker'); ?></label></th>
                        <td>
                            <input name="mc_api_key" type="text" id="mc_api_key" value="<?php echo $api_key; ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Ejemplo: 1234567890abcdef-us1 (el sufijo -usX indica el datacenter).', 'seocontentlocker'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="mc_account"><?php esc_html_e('Account label', 'seocontentlocker'); ?></label></th>
                        <td>
                            <input name="mc_account" type="text" id="mc_account" value="<?php echo $account; ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('Etiqueta para identificar la cuenta (opcional).', 'seocontentlocker'); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="mc_list_id"><?php esc_html_e('Audience / List ID', 'seocontentlocker'); ?></label></th>
                        <td>
                            <input name="mc_list_id" type="text" id="mc_list_id" value="<?php echo $list_id; ?>" class="regular-text" />
                            <p class="description"><?php esc_html_e('ID de la lista/audience en Mailchimp (puede obtenerse desde la UI de Mailchimp).', 'seocontentlocker'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button(__('Guardar ajustes de Mailchimp', 'seocontentlocker')); ?>
        </form>

        <h2><?php esc_html_e('Probar conexión', 'seocontentlocker'); ?></h2>
        <p><?php esc_html_e('Una vez guardada la API Key, podés probar la conexión con Mailchimp para verificar que la clave es válida.', 'seocontentlocker'); ?></p>

        <p>
            <button id="mc-test-connection" class="button"><?php esc_html_e('Probar conexión', 'seocontentlocker'); ?></button>
            <span id="mc-test-result" class="success">Conexión OK</span>
        </p>
    </div>
    <?php
}

/* === Encolar JS solo en la página de ajustes === */
add_action('admin_enqueue_scripts', 'seocontentlocker_mailchimp_admin_scripts');
function seocontentlocker_mailchimp_admin_scripts($hook_suffix) {
    // Slug de la página: 'seo-locker_page_seocontentlocker-mailchimp'
    if ($hook_suffix !== 'seo-locker_page_seocontentlocker-mailchimp') return;

    $plugin_root = dirname(__DIR__); // un nivel arriba de includes
    $js_file = $plugin_root . '/assets/locker-mailchimp.js';
    $js_url  = plugins_url( '../assets/locker-mailchimp.js', __FILE__ );

    if (file_exists($js_file)) {
        wp_enqueue_script('seocontentlocker-mailchimp-admin', $js_url, ['jquery'], filemtime($js_file), true);
    } else {
        wp_enqueue_script('seocontentlocker-mailchimp-admin', $js_url, ['jquery'], false, true);
    }

    wp_localize_script('seocontentlocker-mailchimp-admin', 'seocontentlocker_mailchimp', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('seocontentlocker_mailchimp_nonce'),
    ]);
}

