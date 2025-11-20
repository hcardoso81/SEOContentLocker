<?php

if (!defined('ABSPATH')) exit;


add_action('admin_init', 'seocontentlocker_register_recaptcha_settings');

function seocontentlocker_register_recaptcha_settings()
{
    // Registrar opciones dentro del grupo
    register_setting(
        'seocontentlocker_settings_group',
        'seocontentlocker_recaptcha_site_key',
        ['sanitize_callback' => 'sanitize_text_field']
    );

    register_setting(
        'seocontentlocker_settings_group',
        'seocontentlocker_recaptcha_secret_key',
        ['sanitize_callback' => 'sanitize_text_field']
    );
}

add_action('admin_menu', function () {

    // Menú principal
    add_menu_page(
        'SEO Content Locker',
        'SEO Content Locker',
        'manage_options',
        SLUG,
        'seo_locker_render_main_page',
        'dashicons-lock',
        30
    );

    // Submenú: Leads Same IP
    add_submenu_page(
        SLUG,
        'Leads Same IP',
        'Leads Same IP',
        'manage_options',
        SLUG . '_same_ip',
        'seo_locker_render_same_ip_page'
    );

    // Submenú: reCAPTCHA
    add_submenu_page(
        SLUG,
        'reCAPTCHA Settings',
        'reCAPTCHA',
        'manage_options',
        SLUG . '_recaptcha',
        'seo_locker_render_recaptcha_settings_page'
    );
});

// ======================================================
// Página principal: Leads
// ======================================================
function seo_locker_render_main_page()
{
    $table = new SEO_Locker_Table();
    $table->prepare_items();

    // Mensajes de notificación
    $notices = [
        'exported'      => ['type' => 'info', 'msg' => 'Datos exportados correctamente.'],
        'expired'       => ['type' => 'warning', 'msg' => 'Lead marcado como expirado correctamente.'],
        'deleted'       => ['type' => 'error', 'msg' => 'Lead eliminado correctamente.'],
        'bulk_deleted'  => ['type' => 'error', 'msg' => 'Leads seleccionados eliminados correctamente.'],
        'updated_date'  => ['type' => 'success', 'msg' => 'Fecha de expiración actualizada correctamente.']
    ];

    foreach ($notices as $key => $notice) {
        if (isset($_GET[$key]) && $_GET[$key] == 1) {
            echo '<div class="notice notice-' . esc_attr($notice['type']) . ' is-dismissible"><p>' . esc_html($notice['msg']) . '</p></div>';
        }
    }

?>
    <div class="wrap">
        <h1>SEO Content Locker</h1>

        <?php $table->views(); ?>

        <form method="get">
            <input type="hidden" name="page" value="<?= esc_attr(SLUG) ?>" />
            <?php
            if (!empty($_GET['orderby'])) {
                echo '<input type="hidden" name="orderby" value="' . esc_attr($_GET['orderby']) . '" />';
            }
            if (!empty($_GET['order'])) {
                echo '<input type="hidden" name="order" value="' . esc_attr($_GET['order']) . '" />';
            }

            $table->search_box('Buscar lead', 'lead_search');
            $table->display();
            ?>
        </form>
    </div>
<?php
}

// ======================================================
// Modal de edición condicional
// ======================================================
function seo_locker_edit_date_modal_conditional()
{
    global $pagenow;
    if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === SLUG) {
        new_seo_locker_edit_date_modal();
    }
}
add_action('admin_footer', 'seo_locker_edit_date_modal_conditional');

// ======================================================
// Página Submenú: Leads Same IP
// ======================================================
function seo_locker_render_same_ip_page()
{
    // Carga condicional de la clase
    if (!class_exists('SEO_Locker_Table_Same_IP')) {
        require_once plugin_dir_path(__FILE__) . 'class-seo-locker-table-same-ip.php';
    }

    $table = new SEO_Locker_Table_Same_IP();
    $table->prepare_items();

    echo '<div class="wrap"><h1>Leads Same IP</h1>';
    $table->display();
    echo '</div>';
}
