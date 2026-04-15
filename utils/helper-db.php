<?php
if (!defined('ABSPATH')) exit;

function seocontentlocker_lead_repository() {
    static $repository = null;

    if ($repository === null) {
        $repository = new LeadRepository();
    }

    return $repository;
}

function seocontentlocker_same_ip_repository() {
    static $repository = null;

    if ($repository === null) {
        $repository = new SameIpRepository();
    }

    return $repository;
}

/**
 * ========== TABLAS ==========
 */
function db_table_leads() {
    return seocontentlocker_lead_repository()->tableName();
}

function db_table_same_ip() {
    return seocontentlocker_same_ip_repository()->tableName();
}

function db_insert_same_ip($ip, $country, $email, $slug)
{
    return seocontentlocker_same_ip_repository()->insert($ip, $country, $email, $slug);
}

/**
 * ========== CRUD LEAD ==========
 */
function db_get_lead_by_id($id) {
    return seocontentlocker_lead_repository()->findById($id);
}

function db_get_lead_by_ip($ip)
{
    return seocontentlocker_lead_repository()->findByIp($ip);
}

function db_get_lead_by_email($email) {
    return seocontentlocker_lead_repository()->findByEmail($email);
}

function db_insert_lead($email, $ip, $country, $slug, $days = 15) {
    return seocontentlocker_lead_repository()->insert($email, $ip, $country, $slug, $days);
}

function db_update_expire_date($id, $datetime) {
    return seocontentlocker_lead_repository()->updateExpireDate($id, $datetime);
}

function db_expire_lead_now($id) {
    return seocontentlocker_lead_repository()->expireNow($id);
}

function db_delete_lead($id) {
    return seocontentlocker_lead_repository()->delete($id);
}

/**
 * ========== MASIVOS ==========
 */
function db_bulk_delete_leads($ids) {
    return seocontentlocker_lead_repository()->bulkDelete($ids);
}

/**
 * ========== LIST TABLE HELPERS ==========
 */
function db_count_leads($search = null) {
    return seocontentlocker_lead_repository()->count($search);
}

function db_get_leads($orderby, $order, $per_page, $offset, $search = null) {
    return seocontentlocker_lead_repository()->search($orderby, $order, $per_page, $offset, $search);
}
