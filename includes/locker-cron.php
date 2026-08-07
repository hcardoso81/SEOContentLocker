<?php
if (!defined('ABSPATH')) exit;

define('SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK', 'seocontentlocker_day_13_report');
define('SEO_CONTENT_LOCKER_CRON_SCHEDULE_VERSION', '1.1.18');

add_action(SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK, 'seocontentlocker_run_day_13_report');

function seocontentlocker_schedule_day_13_report()
{
    $scheduledVersion = get_option('seo_content_locker_cron_schedule_version', '');
    $timestamp = wp_next_scheduled(SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK);

    if ($timestamp && $scheduledVersion === SEO_CONTENT_LOCKER_CRON_SCHEDULE_VERSION) {
        return;
    }

    while ($timestamp) {
        wp_unschedule_event($timestamp, SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK);
        $timestamp = wp_next_scheduled(SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK);
    }

    $timezone = new DateTimeZone(SEO_CONTENT_LOCKER_REPORT_TIMEZONE);
    $nextRun = new DateTime('now', $timezone);
    $nextRun->setTime(10, 0, 0);

    $now = new DateTime('now', $timezone);

    if ($nextRun <= $now) {
        $nextRun->modify('+1 day');
    }

    wp_schedule_event($nextRun->getTimestamp(), 'daily', SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK);
    update_option('seo_content_locker_cron_schedule_version', SEO_CONTENT_LOCKER_CRON_SCHEDULE_VERSION);
}

function seocontentlocker_unschedule_day_13_report()
{
    $timestamp = wp_next_scheduled(SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK);

    if ($timestamp) {
        wp_unschedule_event($timestamp, SEO_CONTENT_LOCKER_DAY_13_REPORT_HOOK);
    }

    delete_option('seo_content_locker_cron_schedule_version');
}

function seocontentlocker_run_day_13_report()
{
    if (!defined('LOCKER_REPORT_EMAIL') || !is_email(LOCKER_REPORT_EMAIL)) {
        scl_write_log('day-13-report.log', [
            'status' => 'invalid_report_email',
        ]);
        return;
    }

    $service = new \SeoContentLocker\Services\Day13LeadReportService();
    $service->sendDailyReport();
}
