<?php

namespace App\Console\Commands;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsCheckEscalationsCommand extends Command
{
    protected $signature = 'notifications:check-escalations {--minutes=30 : SLA before re-firing as CRITICAL}';
    protected $description = 'Escalate URGENT/CRITICAL notifications that remain unread past the SLA.';

    public function handle(NotificationService $notifier): int
    {
        $minutes = (int) $this->option('minutes');
        $cutoff = now()->subMinutes($minutes);

        $rows = DatabaseNotification::query()
            ->whereNull('read_at')
            ->where('created_at', '<', $cutoff)
            ->where(function ($q) {
                $q->where('data', 'like', '%"priority":"URGENT"%')
                  ->orWhere('data', 'like', '%"priority":"CRITICAL"%');
            })
            ->where('data', 'not like', '%"escalated":true%')
            ->limit(500)
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $user = \App\Models\User::find($row->notifiable_id);
            if (! $user) {
                continue;
            }
            $original = (array) $row->data;
            $payload = array_merge($original, [
                'priority' => NotificationPriority::CRITICAL,
                'title' => '[ESCALATED] ' . ($original['title'] ?? 'Action required'),
                'message' => '[ESCALATED] ' . ($original['message'] ?? ''),
                'source_type' => 'escalation:' . ($original['source_type'] ?? 'notification'),
                'source_id' => $row->id,
                'escalated' => true,
            ]);

            if ($notifier->notifyUser($user, $payload, 0)) {
                $count++;
            }
        }

        $this->info("Escalations dispatched: {$count}");
        return self::SUCCESS;
    }
}
