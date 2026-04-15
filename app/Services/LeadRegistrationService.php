<?php
if (!defined('ABSPATH')) exit;

class LeadRegistrationService
{
    private $recaptchaService;
    private $mailchimpService;

    public function __construct($recaptchaService = null, $mailchimpService = null)
    {
        $this->recaptchaService = $recaptchaService ?: new RecaptchaService();
        $this->mailchimpService = $mailchimpService ?: new MailchimpService();
    }

    public function register($email, $slug, $ip, $recaptchaToken)
    {
        $this->recaptchaService->validate($recaptchaToken);

        $leadResult = check_lead($email, $slug);
        if ($leadResult) {
            return $leadResult;
        }

        $ipResult = check_ip($ip, $email, $slug);
        if ($ipResult) {
            return $ipResult;
        }

        save_lead($email, $slug, $ip);

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
                SeoContentLockerEvents::MAILCHIMP_FAILED,
                [
                    'email' => $email,
                    'ip' => $ip,
                    'slug' => $slug,
                    'mailchimp_error' => $mcResponse,
                ]
            );
        } else {
            seocontentlocker_dispatch_event(
                SeoContentLockerEvents::LEAD_CREATED_SUCCESS,
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
}
