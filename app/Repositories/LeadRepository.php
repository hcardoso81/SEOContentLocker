<?php
if (!defined('ABSPATH')) exit;

class LeadRepository
{
    public function tableName()
    {
        global $wpdb;
        return $wpdb->prefix . 'leads_subscriptions';
    }

    public function findById($id)
    {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tableName()} WHERE id=%d", $id)
        );
    }

    public function findByIp($ip)
    {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tableName()} WHERE ip = %s LIMIT 1",
                $ip
            )
        );
    }

    public function findByEmail($email)
    {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->tableName()} WHERE email=%s", $email)
        );
    }

    public function insert($email, $ip, $country, $slug, $days = 15)
    {
        global $wpdb;

        $created = new DateTime();
        $expires = (clone $created)->modify("+{$days} days");

        return $wpdb->insert(
            $this->tableName(),
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

    public function updateExpireDate($id, $datetime)
    {
        global $wpdb;
        return $wpdb->update(
            $this->tableName(),
            ['expires_at' => $datetime],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    public function expireNow($id)
    {
        global $wpdb;
        $expiredDate = date('Y-m-d H:i:s', strtotime('-1 second', current_time('timestamp')));

        return $wpdb->update(
            $this->tableName(),
            ['expires_at' => $expiredDate, 'status' => 'expired'],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function delete($id)
    {
        global $wpdb;
        return $wpdb->delete($this->tableName(), ['id' => $id], ['%d']);
    }

    public function bulkDelete($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->tableName()} WHERE id IN ($placeholders)",
                ...$ids
            )
        );
    }

    public function count($search = null)
    {
        global $wpdb;
        $where = "WHERE 1=1";

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                " AND (email LIKE %s OR country LIKE %s)",
                $like,
                $like
            );
        }

        return (int) $wpdb->get_var("SELECT COUNT(id) FROM {$this->tableName()} $where");
    }

    public function search($orderby, $order, $perPage, $offset, $search = null)
    {
        global $wpdb;

        $validOrderBy = ['email', 'country', 'created_at', 'expires_at', 'post_slug'];
        $safeOrderBy = in_array($orderby, $validOrderBy, true) ? $orderby : 'created_at';
        $safeOrder = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $where = "WHERE 1=1";

        if (!empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                " AND (email LIKE %s OR country LIKE %s)",
                $like,
                $like
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tableName()} $where ORDER BY $safeOrderBy $safeOrder LIMIT %d OFFSET %d",
                $perPage,
                $offset
            )
        );
    }

    public function exportRows()
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT id, email, ip, country, post_slug, status, created_at, expires_at
             FROM {$this->tableName()}
             ORDER BY created_at DESC"
        );
    }
}
