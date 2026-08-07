<?php
namespace SeoContentLocker\Services;

use DateTimeImmutable;
use SeoContentLocker\Repositories\LeadRepository;

if (!defined('ABSPATH')) exit;

class Day13LeadReportService
{
    private $leadRepository;

    public function __construct($leadRepository = null)
    {
        $this->leadRepository = $leadRepository ?: new LeadRepository();
    }

    public function sendDailyReport()
    {
        $timezone = wp_timezone();
        $today = new DateTimeImmutable('today', $timezone);
        $start = $today->modify('-13 days');
        $end = $start->modify('+1 day');
        $reportDate = $start->format('Y-m-d');
        $lockKey = 'seocontentlocker_day13_report_' . $reportDate;

        if (get_transient($lockKey)) {
            scl_write_log('day-13-report.log', [
                'status' => 'skipped_duplicate',
                'report_date' => $reportDate,
            ]);
            return false;
        }

        $leads = $this->leadRepository->findCreatedBetween(
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s')
        );

        if (empty($leads)) {
            scl_write_log('day-13-report.log', [
                'status' => 'no_leads',
                'report_date' => $reportDate,
                'from' => $start->format('Y-m-d H:i:s'),
                'to' => $end->format('Y-m-d H:i:s'),
            ]);
            set_transient($lockKey, true, 2 * DAY_IN_SECONDS);
            return true;
        }

        $subject = sprintf(
            'Reporte de leads con 13 dias - %s',
            $start->format('d/m/Y')
        );
        $html = $this->buildHtmlMessage($leads, $start, $end);
        $plain = $this->buildPlainMessage($leads, $start, $end);
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        $mailFailedHandler = function ($wpError) use ($reportDate, $leads) {
            log_error($wpError, 'day_13_report_mail_failed', LOCKER_REPORT_EMAIL);
            scl_write_log('day-13-report.log', [
                'status' => 'mail_failed_hook',
                'report_date' => $reportDate,
                'lead_count' => count($leads),
                'error' => $wpError,
            ]);
        };
        $phpmailerHandler = function ($phpmailer) use ($plain) {
            $phpmailer->AltBody = $plain;
        };

        add_action('wp_mail_failed', $mailFailedHandler);
        add_action('phpmailer_init', $phpmailerHandler);

        $sent = wp_mail(LOCKER_REPORT_EMAIL, $subject, $html, $headers);

        remove_action('wp_mail_failed', $mailFailedHandler);
        remove_action('phpmailer_init', $phpmailerHandler);

        if (!$sent) {
            log_error(
                'wp_mail returned false for day 13 report',
                'day_13_report',
                LOCKER_REPORT_EMAIL
            );
            scl_write_log('day-13-report.log', [
                'status' => 'mail_failed',
                'report_date' => $reportDate,
                'lead_count' => count($leads),
            ]);
            return false;
        }

        set_transient($lockKey, true, 2 * DAY_IN_SECONDS);
        scl_write_log('day-13-report.log', [
            'status' => 'sent',
            'report_date' => $reportDate,
            'lead_count' => count($leads),
            'emails' => wp_list_pluck($leads, 'email'),
        ]);

        return true;
    }

    private function buildHtmlMessage($leads, $start, $end)
    {
        $dateLabel = esc_html($start->format('d/m/Y'));
        $rows = '';

        foreach ($leads as $lead) {
            $rows .= '<tr>';
            $rows .= '<td style="padding:10px;border:1px solid #d9dee7;">' . esc_html($lead->first_name ?: '-') . '</td>';
            $rows .= '<td style="padding:10px;border:1px solid #d9dee7;">' . esc_html($lead->email) . '</td>';
            $rows .= '<td style="padding:10px;border:1px solid #d9dee7;white-space:nowrap;">' . esc_html($this->formatDate($lead->created_at)) . '</td>';
            $rows .= '<td style="padding:10px;border:1px solid #d9dee7;">' . esc_html($lead->country ?: '-') . '</td>';
            $rows .= '</tr>';
        }

        return '<div style="font-family:Arial,sans-serif;color:#263238;max-width:900px;">'
            . '<h2 style="color:#17324d;">Reporte de leads con 13 dias</h2>'
            . '<p>Estos leads ingresaron al sistema durante el dia <strong>' . $dateLabel . '</strong>.</p>'
            . '<p>Total: <strong>' . count($leads) . '</strong></p>'
            . '<table style="border-collapse:collapse;width:100%;font-size:14px;">'
            . '<thead><tr style="background:#17324d;color:#fff;">'
            . '<th style="padding:10px;text-align:left;">First Name</th>'
            . '<th style="padding:10px;text-align:left;">Email</th>'
            . '<th style="padding:10px;text-align:left;">Fecha de ingreso</th>'
            . '<th style="padding:10px;text-align:left;">Pais</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p style="font-size:12px;color:#68737d;margin-top:20px;">Ventana consultada: '
            . esc_html($start->format('d/m/Y H:i:s')) . ' a ' . esc_html($end->format('d/m/Y H:i:s')) . ' (zona horaria de WordPress).</p>'
            . '</div>';
    }

    private function buildPlainMessage($leads, $start, $end)
    {
        $lines = [
            'Reporte de leads con 13 dias',
            'Fecha de ingreso: ' . $start->format('d/m/Y'),
            'Total: ' . count($leads),
            '',
        ];

        foreach ($leads as $lead) {
            $lines[] = sprintf(
                '- %s | %s | %s | %s',
                $lead->first_name ?: '-',
                $lead->email,
                $this->formatDate($lead->created_at),
                $lead->country ?: '-'
            );
        }

        return implode("\n", $lines);
    }

    private function formatDate($date)
    {
        $parsed = new DateTimeImmutable($date, wp_timezone());
        return $parsed->format('d/m/Y H:i');
    }
}
