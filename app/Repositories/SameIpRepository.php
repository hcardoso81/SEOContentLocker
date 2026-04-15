<?php
namespace SeoContentLocker\Repositories;

if (!defined('ABSPATH')) exit;

class SameIpRepository
{
    public function tableName()
    {
        global $wpdb;
        return $wpdb->prefix . 'leads_subscriptions_same_ip';
    }

    public function insert($ip, $country, $email, $slug)
    {
        global $wpdb;

        return $wpdb->insert($this->tableName(), [
            'ip' => $ip,
            'email' => $email,
            'country' => $country,
            'post_slug' => $slug,
            'created_at' => current_time('mysql')
        ]);
    }

    public function count()
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->tableName()}");
    }

    public function paginate($perPage, $offset)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tableName()} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $perPage,
                $offset
            )
        );
    }
}
