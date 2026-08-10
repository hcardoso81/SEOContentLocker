<?php
if (!defined('ABSPATH')) exit;

spl_autoload_register(function ($class) {
    static $classMap = [
        'SeoContentLocker\\Repositories\\LeadRepository' => 'app/Repositories/LeadRepository.php',
        'SeoContentLocker\\Repositories\\SameIpRepository' => 'app/Repositories/SameIpRepository.php',
        'SeoContentLocker\\Services\\LeadAccessService' => 'app/Services/LeadAccessService.php',
        'SeoContentLocker\\Services\\LeadRegistrationService' => 'app/Services/LeadRegistrationService.php',
        'SeoContentLocker\\Services\\RecaptchaService' => 'app/Services/RecaptchaService.php',
        'SeoContentLocker\\Services\\AntiBotProtectionService' => 'app/Services/AntiBotProtectionService.php',
        'SeoContentLocker\\Services\\RateLimitService' => 'app/Services/RateLimitService.php',
        'SeoContentLocker\\Services\\MailchimpService' => 'app/Services/MailchimpService.php',
        'SeoContentLocker\\Admin\\LeadTable' => 'admin/class-seo-locker-table.php',
        'SeoContentLocker\\Admin\\SameIpTable' => 'admin/class-seo-locker-table-same-ip.php',
        'SeoContentLocker\\Notifier\\Notifier' => 'app/Notifier/Notifier.php',
        'SeoContentLocker\\Notifier\\Events' => 'app/Notifier/Events.php',
        'SeoContentLocker\\Notifier\\Dispatcher' => 'app/Notifier/Dispatcher.php',
        'SeoContentLocker\\Services\\Day13LeadReportService' => 'app/Services/Day13LeadReportService.php',
    ];

    if (!isset($classMap[$class])) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . $classMap[$class];
});
