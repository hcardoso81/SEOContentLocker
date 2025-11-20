<?php
if (!defined('ABSPATH')) exit;

/**
 * ========== TABLAS ==========
 */
function db_table_leads() {
    global $wpdb;
    return $wpdb->prefix . 'leads_subscriptions';
}

function db_table_same_ip() {
    global $wpdb;
    return $wpdb->prefix . 'leads_subscriptions_same_ip';
}

function db_insert_same_ip($ip, $country, $email, $slug)
{
    global $wpdb;
    $table = db_table_same_ip();

    $wpdb->insert($table, [
        'ip'      => $ip,
        'email'   => $email,
        'country' => $country,
        'post_slug'    => $slug,
        'created_at' => current_time('mysql')
    ]);
}

/**
 * ========== CRUD LEAD ==========
 */

function db_get_lead_by_id($id) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM " . db_table_leads() . " WHERE id=%d", $id)
    );
}

function db_get_lead_by_ip($ip)
{
    global $wpdb;
    $table = db_table_leads();

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE ip = %s LIMIT 1",
            $ip
        )
    );
}

function db_get_lead_by_email($email) {
    global $wpdb;
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM " . db_table_leads() . " WHERE email=%s", $email)
    );
}

function db_insert_lead($email, $ip, $country, $slug, $days = 40) {
    global $wpdb;

    $created = new DateTime();
    $expires = (clone $created)->modify("+{$days} days");

    return $wpdb->insert(
        db_table_leads(),
        [
            'email'      => $email,
            'ip'         => $ip,
            'country'    => $country,
            'post_slug'  => $slug,
            'created_at' => $created->format('Y-m-d H:i:s'),
            'expires_at' => $expires->format('Y-m-d H:i:s'),
        ]
    );
}

function db_update_expire_date($id, $datetime) {
    global $wpdb;
    return $wpdb->update(
        db_table_leads(),
        ['expires_at' => $datetime],
        ['id' => $id],
        ['%s'],
        ['%d']
    );
}

function db_expire_lead_now($id) {
    global $wpdb;
    $expired_date = date('Y-m-d H:i:s', strtotime('-1 second', current_time('timestamp')));
    return $wpdb->update(
        db_table_leads(),
        ['expires_at' => $expired_date, 'status' => 'expired'],
        ['id' => $id],
        ['%s', '%s'],
        ['%d']
    );
}

function db_delete_lead($id) {
    global $wpdb;
    return $wpdb->delete(db_table_leads(), ['id' => $id], ['%d']);
}

/**
 * ========== MASIVOS ==========
 */
function db_bulk_delete_leads($ids) {
    if (empty($ids)) return 0;

    global $wpdb;

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));

    return $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM " . db_table_leads() . " WHERE id IN ($placeholders)",
            ...$ids
        )
    );
}

/**
 * ========== LIST TABLE HELPERS ==========
 */
function db_count_leads($search = null) {
    global $wpdb;
    $table = db_table_leads();

    $where = "WHERE 1=1";

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= $wpdb->prepare(
            " AND (email LIKE %s OR country LIKE %s)",
            $like, $like
        );
    }

    return (int) $wpdb->get_var("SELECT COUNT(id) FROM $table $where");
}

function db_get_leads($orderby, $order, $per_page, $offset, $search = null) {
    global $wpdb;
    $table = db_table_leads();

    $where = "WHERE 1=1";

    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= $wpdb->prepare(
            " AND (email LIKE %s OR country LIKE %s)",
            $like, $like
        );
    }

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY $orderby $order LIMIT %d OFFSET %d",
            $per_page,
            $offset
        )
    );
}
