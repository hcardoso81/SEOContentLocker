<?php
if (!defined('ABSPATH')) exit;

spl_autoload_register(function ($class) {
    static $classMap = [
        'SeoContentLocker\\Repositories\\LeadRepository' => 'app/Repositories/LeadRepository.php',
        'SeoContentLocker\\Repositories\\SameIpRepository' => 'app/Repositories/SameIpRepository.php',
        'SeoContentLocker\\Services\\LeadAccessService' => 'app/Services/LeadAccessService.php',
        'SeoContentLocker\\Services\\LeadRegistrationService' => 'app/Services/LeadRegistrationService.php',
        'SeoContentLocker\\Services\\RecaptchaService' => 'app/Services/RecaptchaService.php',
        'SeoContentLocker\\Services\\MailchimpService' => 'app/Services/MailchimpService.php',
        'SeoContentLocker\\Admin\\LeadTable' => 'admin/class-seo-locker-table.php',
        'SeoContentLocker\\Admin\\SameIpTable' => 'admin/class-seo-locker-table-same-ip.php',
        'SeoContentLocker\\Notifier\\Notifier' => 'includes/notifier/locker-notifier.php',
        'SeoContentLocker\\Notifier\\Events' => 'includes/notifier/locker-events.php',
        'SeoContentLocker\\Notifier\\Dispatcher' => 'includes/notifier/locker-dispatcher.php',
    ];

    if (!isset($classMap[$class])) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . $classMap[$class];
});
