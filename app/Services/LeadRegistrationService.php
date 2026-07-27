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

    public function register($email, $firstName, $slug, $ip, $recaptchaToken, $validateRecaptcha = true, $notifyRestore = false)
    {
        if ($validateRecaptcha) {
            $this->recaptchaService->validate($recaptchaToken);
        }

        $leadResult = $this->accessService->checkLead($email, $slug, false, $notifyRestore, $ip);
        if ($leadResult) {
            return $leadResult;
        }

        $ipResult = $this->accessService->checkIp($ip, $email, $slug);
        if ($ipResult) {
            return $ipResult;
        }

        $country = get_country_from_ip($ip);

        $this->saveLead($email, $firstName, $slug, $ip, $country);

        $mcResponse = $this->mailchimpService->subscribe($email, $firstName, $slug);

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
                    'country' => $country,
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
                    'country' => $country,
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

    public function saveLead($email, $firstName, $slug, $ip = null, $country = null)
    {
        $ip = $ip ?: get_ip();
        $country = $country !== null ? $country : get_country_from_ip($ip);

        log_suscription($email, $ip, $country);

        return $this->leadRepository->insert($email, $firstName, $ip, $country, $slug);
    }
}
