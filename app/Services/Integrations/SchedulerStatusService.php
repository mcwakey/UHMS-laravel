<?php

namespace App\Services\Integrations;

use App\Models\Setting;

/**
 * Records and reads the last-run status of the integration scheduler commands so
 * the Scheduler Status admin screen can show whether cron is actually firing.
 */
class SchedulerStatusService
{
    public const GROUP = 'integrations_scheduler';

    /** command signature => recommended cadence (display only) */
    public const COMMANDS = [
        'integrations:sms-reconcile-status' => 'hourly',
        'integrations:payments-recheck-pending' => 'everyFifteenMinutes',
        'integrations:sms-send-appointment-reminders' => 'dailyAt 08:00',
    ];

    public function recordRun(string $command, string $status, array $summary = []): void
    {
        Setting::setValue(self::GROUP, $command, [
            'last_run' => now()->toIso8601String(),
            'last_status' => $status,
            'summary' => $summary,
        ], 'json');
    }

    /** @return array<string,array> command => [recommended, last_run, last_status, summary] */
    public function all(): array
    {
        $out = [];
        foreach (self::COMMANDS as $command => $recommended) {
            $stored = Setting::getValue(self::GROUP, $command, null);
            $stored = is_array($stored) ? $stored : [];
            $out[$command] = [
                'recommended' => $recommended,
                'last_run' => $stored['last_run'] ?? null,
                'last_status' => $stored['last_status'] ?? null,
                'summary' => $stored['summary'] ?? [],
            ];
        }
        return $out;
    }
}
