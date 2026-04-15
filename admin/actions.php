<?php
if (!defined('ABSPATH')) exit;


function seocontentlocker_build_redirect_params($success_param)
{
    $request = wp_unslash($_REQUEST);

    $params = [
        'page'    => SLUG,
        'orderby' => sanitize_text_field($request['orderby'] ?? 'created_at'),
        'order'   => strtolower(sanitize_text_field($request['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc',
        $success_param => 1,
    ];

    if (!empty($request['s'])) {
        $params['s'] = sanitize_text_field($request['s']);
    }

    if (!empty($request['paged'])) {
        $params['paged'] = intval($request['paged']);
    }

    wp_redirect(add_query_arg($params, admin_url('admin.php')));
    exit;
}

add_action('admin_post_seocontentlocker_expire_lead', 'seocontentlocker_expire_lead_handler');
add_action('admin_post_seocontentlocker_delete_lead', 'seocontentlocker_delete_lead_handler');
add_action('admin_post_seocontentlocker_update_expire_date', 'seocontentlocker_update_expire_date_handler');
add_action('admin_post_seocontentlocker_export_csv', 'seocontentlocker_export_csv_handler');
add_action('load-toplevel_page_' . SLUG, 'seocontentlocker_handle_bulk_actions');

function seocontentlocker_expire_lead_handler()
{
    verify_permission('manage_options');

    $id = intval($_GET['id'] ?? 0);
    check_admin_referer('expire_' . $id);
    seocontentlocker_lead_repository()->expireNow($id);

    seocontentlocker_build_redirect_params('expired');
}

function seocontentlocker_delete_lead_handler()
{
    verify_permission('manage_options');

    $id = intval($_GET['id'] ?? 0);
    check_admin_referer('delete_' . $id);
    seocontentlocker_lead_repository()->delete($id);

    seocontentlocker_build_redirect_params('deleted');
}

function seocontentlocker_handle_bulk_actions()
{
    verify_permission('manage_options');

    $action = $_REQUEST['action'] ?? ($_REQUEST['action2'] ?? '');

    if ($action === 'bulk_delete') {
        check_admin_referer('bulk-leads');
        $ids = array_map('intval', $_REQUEST['lead'] ?? []);
        seocontentlocker_lead_repository()->bulkDelete($ids);
        seocontentlocker_build_redirect_params('bulk_deleted');
    }
}

function seocontentlocker_update_expire_date_handler()
{
    verify_permission('manage_options');
    check_admin_referer('update_expire_date');

    $id = intval($_POST['id'] ?? 0);
    $date = sanitize_text_field($_POST['new_expire_date']);

    // Convertir "11/11/2025" → DateTime
    $dateObj = DateTime::createFromFormat('d/m/Y', $date);

    if (!$dateObj) {
        wp_send_json_error(['message' => 'Formato de fecha inválido']);
    }

    // Formato MySQL correcto
    $mysql_datetime = $dateObj->format('Y-m-d 23:59:59');

    // Ejecutar update real
    seocontentlocker_lead_repository()->updateExpireDate($id, $mysql_datetime);

    seocontentlocker_build_redirect_params('updated_date');
}

function seocontentlocker_export_csv_handler()
{
    try {
        verify_permission('manage_options');
        check_admin_referer('seocontentlocker_export_csv');

        $results = seocontentlocker_lead_repository()->exportRows();

        if (empty($results)) {
            wp_die(__('No leads found for export.', 'seocontentlocker'));
        }

        // Configurar headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=leads-' . date('Y-m-d-His') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // UTF-8 BOM para que Excel lo abra correctamente
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Encabezados
        fputcsv($output, ['ID', 'Email', 'IP', 'País', 'Post Slug', 'Fecha Alta', 'Expira']);

        // Datos
        foreach ($results as $row) {
            fputcsv($output, [
                $row->id ?? '',
                $row->email ?? '',
                $row->ip ?? '',
                $row->country ?? '',
                $row->post_slug ?? '',
                $row->created_at ?? '',
                $row->expires_at ?? ''
            ]);
        }

        fclose($output);
        exit;
    } catch (Exception $e) {
        log_error($e, 'export_csv');
        wp_die('Error exporting CSV: ' . $e->getMessage());
    }
}
