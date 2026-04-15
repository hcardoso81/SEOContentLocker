<?php
if (!defined('ABSPATH')) exit;

class LeadAccessService
{
    public function checkStatus($email, $slug, $ip)
    {
        $leadResult = check_lead($email, $slug);
        if ($leadResult) {
            return $leadResult;
        }

        $ipResult = check_ip($ip, $email, $slug, false);
        if ($ipResult) {
            return $ipResult;
        }

        return [
            'status' => 'success',
            'message' => 'checked lead status',
        ];
    }
}
