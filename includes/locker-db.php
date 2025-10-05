<?php
if (!defined('ABSPATH')) exit;

function seocontentlocker_db_table() {
    global $wpdb;
    return $wpdb->prefix . 'leads_subscriptions';
}

function seocontentlocker_db_exists($email, $ip, $status) {
    global $wpdb;
    $table = seocontentlocker_db_table();
    return $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table WHERE (email=%s OR ip=%s) AND status=%s",
        $email, $ip, $status
    ));
}

function seocontentlocker_db_insert_pending($email, $ip, $country, $slug) {
    global $wpdb;
    $table = seocontentlocker_db_table();

    return $wpdb->insert($table, [
        'email'      => $email,
        'ip'         => $ip,
        'country'    => $country,
        'created_at' => current_time('mysql', 1),
        'post_slug'  => $slug,
        'status'     => 'pending'
    ], ['%s', '%s', '%s', '%s', '%s', '%s']);
}

function seocontentlocker_db_update_confirmed($email) {
    global $wpdb;
    $table = seocontentlocker_db_table();
    return $wpdb->update($table, ['status' => 'confirmed'], ['email' => $email], ['%s'], ['%s']);
}

function seocontentlocker_db_get_all() {
    global $wpdb;
    $table = seocontentlocker_db_table();
    return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);
}

function seocontentlocker_db_delete($id) {
    global $wpdb;
    $table = seocontentlocker_db_table();
    return $wpdb->delete($table, ['id' => $id], ['%d']);
}
