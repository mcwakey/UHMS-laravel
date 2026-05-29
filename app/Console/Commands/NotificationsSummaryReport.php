<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class NotificationsSummaryReport extends Command
{
    protected $signature = 'reports:notifications-summary {--days=30}';
    protected $description = 'Per-module summary of sent / read / unread notifications.';

    public function handle(): int
    {
        $since = now()->subDays((int) $this->option('days'));

        $rows = DatabaseNotification::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('json_extract(data, "$.module") as module')
            ->selectRaw('count(*) as sent')
            ->selectRaw('sum(case when read_at is not null then 1 else 0 end) as read_count')
            ->groupBy('module')
            ->orderByDesc('sent')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No notifications in window.');
            return self::SUCCESS;
        }

        $this->table(
            ['Module', 'Sent', 'Read', 'Unread', 'Read %'],
            $rows->map(function ($r) {
                $module = trim((string) $r->module, '"') ?: 'SYSTEM';
                $sent = (int) $r->sent;
                $read = (int) $r->read_count;
                $unread = $sent - $read;
                $pct = $sent > 0 ? round($read / $sent * 100, 1) : 0;
                return [$module, $sent, $read, $unread, "{$pct}%"];
            })->all()
        );

        return self::SUCCESS;
    }
}
