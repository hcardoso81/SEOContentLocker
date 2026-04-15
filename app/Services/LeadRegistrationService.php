<?php
namespace SeoContentLocker\Services;

use SeoContentLocker\Repositories\LeadRepository;
use SeoContentLocker\Notifier\Events;

if (!defined('ABSPATH')) exit;

class LeadRegistrationService
{
    private $recaptchaService;
    private $mailchimpService;
    private $leadRepository;
    private $accessService;

    public function __construct($recaptchaService = null, $mailchimpService = null, $leadRepository = null, $accessService = null)
    {
        $this->recaptchaService = $recaptchaService ?: new RecaptchaService();
        $this->mailchimpService = $mailchimpService ?: new MailchimpService();
        $this->leadRepository = $leadRepository ?: new LeadRepository();
        $this->accessService = $accessService ?: new LeadAccessService($this->leadRepository);
    }

    public function register($email, $slug, $ip, $recaptchaToken)
    {
        $this->recaptchaService->validate($recaptchaToken);

        $leadResult = $this->accessService->checkLead($email, $slug);
        if ($leadResult) {
            return $leadResult;
        }

        $ipResult = $this->accessService->checkIp($ip, $email, $slug);
        if ($ipResult) {
            return $ipResult;
        }

        $this->saveLead($email, $slug, $ip);

        $mcResponse = $this->mailchimpService->subscribe($email, $slug);

        if (!$mcResponse['success']) {
            log_error(
                'Mailchimp subscription failed',
                'mailchimp_subscribe',
                [
                    'email' => $email,
                    'mailchimp_error' => $mcResponse,
                ]
            );

            seocontentlocker_dispatch_event(
                Events::MAILCHIMP_FAILED,
                [
                    'email' => $email,
                    'ip' => $ip,
                    'slug' => $slug,
                    'mailchimp_error' => $mcResponse,
                ]
            );
        } else {
            seocontentlocker_dispatch_event(
                Events::LEAD_CREATED_SUCCESS,
                [
                    'email' => $email,
                    'ip' => $ip,
                    'slug' => $slug,
                ]
            );
        }

        return [
            'message' => 'Subscription processed',
            'status'  => $mcResponse['success'] ? 'success' : 'mailchimp_failed',
            'mc'      => $mcResponse,
        ];
    }

    public function saveLead($email, $slug, $ip = null)
    {
        $ip = $ip ?: get_ip();
        $country = get_country_from_ip($ip);

        log_suscription($email, $ip, $country);

        return $this->leadRepository->insert($email, $ip, $country, $slug);
    }
}
