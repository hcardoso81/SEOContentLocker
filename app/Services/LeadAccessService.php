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
        $leadResult = $this->checkLead($email, $slug, true, false);
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

    public function checkLead($email, $slug = null, $isStatusCheck = false, $notify = true, $ip = null)
    {
        $lead = $this->leadRepository->findByEmail($email);
        $now = new DateTime();

        if (!$lead || !$lead->expires_at) {
            return null;
        }

        $expireAt = new DateTime($lead->expires_at);
        $leadSlug = $this->resolveLeadSlug($lead, $slug);
        $this->saveMissingLeadSlug($lead, $slug);

        if ($now > $expireAt) {
            log_expires($email, $leadSlug);

            if ($notify) {
                seocontentlocker_dispatch_event(
                    Events::LEAD_EXPIRED,
                    [
                        'email' => $email,
                        'slug'  => $leadSlug,
                    ]
                );
            }

            return [
                'status' => 'expired',
                'message' => 'Your access period has expired',
                'slug' => $leadSlug,
            ];
        }

        if ($isStatusCheck || !$slug) {
            log_restore($email, $leadSlug);
        } else {
            log_access($email, $slug);
        }

        // Las comprobaciones automáticas de estado nunca deben notificar.
        // La notificación de restauración solo corresponde a un envío real
        // del formulario sobre una entrada (post), usando el slug actual.
        if ($notify && !$isStatusCheck && !empty($slug) && $this->shouldNotifyRestore($slug)) {
            seocontentlocker_dispatch_event(
                Events::LEAD_RESTORED,
                [
                    'email' => $email,
                    'slug'  => $slug,
                ]
            );
        }

        if (
            $notify &&
            !$isStatusCheck &&
            !empty($ip) &&
            !empty($lead->ip) &&
            $lead->ip !== $ip
        ) {
            seocontentlocker_dispatch_event(
                Events::LEAD_RESTORED_DIFFERENT_IP,
                [
                    'email' => $email,
                    'slug' => $slug,
                    'registered_ip' => $lead->ip,
                    'current_ip' => $ip,
                ]
            );
        }

        return [
            'status' => 'restored',
            'message' => 'Access restored. Welcome back!',
            'slug' => $leadSlug,
        ];
    }

    private function resolveLeadSlug($lead, $slug = null)
    {
        if (!empty($slug)) {
            return $slug;
        }

        return $lead->post_slug ?? '';
    }

    private function saveMissingLeadSlug($lead, $slug = null)
    {
        if (empty($slug) || !empty($lead->post_slug) || empty($lead->id)) {
            return;
        }

        $this->leadRepository->updatePostSlug((int) $lead->id, $slug);
    }

    private function shouldNotifyRestore($slug)
    {
        if (empty($slug)) {
            return false;
        }

        $postId = url_to_postid(home_url('/' . ltrim($slug, '/') . '/'));

        return $postId && get_post_type($postId) === 'post';
    }

    public function checkIp($ip, $email, $slug = null, $insertSameIp = true)
    {
        if ($this->isReportEmail($email)) {
            return null;
        }

        $existingIp = $this->leadRepository->findByIp($ip);

        if (!$existingIp) {
            return null;
        }

        // El mismo email puede recuperar su acceso desde otra red.
        if (strtolower($existingIp->email) === strtolower($email)) {
            return null;
        }

        $country = get_country_from_ip($ip);

        if ($insertSameIp) {
            log_same_ip($ip, $country, $existingIp->email, $email, $slug);
            $this->sameIpRepository->insert($ip, $country, $email, $slug);
        }

        if ($insertSameIp) {
            seocontentlocker_dispatch_event(
                Events::SAME_IP_BLOCKED,
                [
                    'email' => $email,
                    'current_ip' => $ip,
                    'country' => $country,
                    'existing_email' => $existingIp->email,
                    'assigned_ip' => $existingIp->ip,
                    'slug' => $slug,
                ]
            );
        }

        return [
            'status' => 'same_ip_blocked',
            'message' => 'This IP address is already assigned to another user.',
        ];
    }

    private function isReportEmail($email)
    {
        if (!defined('LOCKER_REPORT_EMAIL') || !LOCKER_REPORT_EMAIL) {
            return false;
        }

        return strtolower($email) === strtolower(LOCKER_REPORT_EMAIL);
    }
}
