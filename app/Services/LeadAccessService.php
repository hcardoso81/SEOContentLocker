<?php
namespace SeoContentLocker\Services;

use DateTime;
use SeoContentLocker\Notifier\Events;
use SeoContentLocker\Repositories\LeadRepository;
use SeoContentLocker\Repositories\SameIpRepository;

if (!defined('ABSPATH')) exit;

class LeadAccessService
{
    private $leadRepository;
    private $sameIpRepository;

    public function __construct($leadRepository = null, $sameIpRepository = null)
    {
        $this->leadRepository = $leadRepository ?: new LeadRepository();
        $this->sameIpRepository = $sameIpRepository ?: new SameIpRepository();
    }

    public function checkStatus($email, $slug, $ip)
    {
        $leadResult = $this->checkLead($email, $slug);
        if ($leadResult) {
            return $leadResult;
        }

        $ipResult = $this->checkIp($ip, $email, $slug, false);
        if ($ipResult) {
            return $ipResult;
        }

        return [
            'status' => 'success',
            'message' => 'checked lead status',
        ];
    }

    public function checkLead($email, $slug = null)
    {
        $lead = $this->leadRepository->findByEmail($email);
        $now = new DateTime();

        if (!$lead || !$lead->expires_at) {
            return null;
        }

        $expireAt = new DateTime($lead->expires_at);

        if ($now > $expireAt) {
            log_expires($email);

            seocontentlocker_dispatch_event(
                Events::LEAD_EXPIRED,
                [
                    'email' => $email,
                    'slug'  => $slug,
                ]
            );

            return [
                'status' => 'expired',
                'message' => 'Your access period has expired',
            ];
        }

        if (!$slug) {
            log_restore($email);

            seocontentlocker_dispatch_event(
                Events::LEAD_RESTORED,
                [
                    'email' => $email,
                    'slug'  => $slug,
                ]
            );
        } else {
            log_access($email, $slug);
        }

        return [
            'status' => 'restored',
            'message' => 'Access restored. Welcome back!',
        ];
    }

    public function checkIp($ip, $email, $slug = null, $insertSameIp = true)
    {
        $existingIp = $this->leadRepository->findByIp($ip);

        if (!$existingIp) {
            return null;
        }

        $country = get_country_from_ip($ip);

        if ($insertSameIp) {
            log_same_ip($ip, $country, $existingIp->email, $email, $slug);
            $this->sameIpRepository->insert($ip, $country, $email, $slug);
        }

        seocontentlocker_dispatch_event(
            Events::SAME_IP_BLOCKED,
            [
                'email' => $email,
                'ip' => $ip,
                'country' => $country,
                'existing_email' => $existingIp->email,
                'slug' => $slug,
            ]
        );

        return [
            'status' => 'expired',
            'message' => 'Multiple leads from same IP',
        ];
    }
}
