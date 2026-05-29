<?php

namespace App\Console\Commands;

use App\Models\NotificationDigestQueue;
use App\Notifications\DatabaseNotification;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotificationsFlushDigestCommand extends Command
{
    protected $signature = 'notifications:flush-digest';
    protected $description = 'Emit buffered digest notifications for users whose quiet hours have ended.';

    public function handle(NotificationService $notifier): int
    {
        $rows = NotificationDigestQueue::query()
            ->whereNull('sent_at')
            ->where('scheduled_for', '<=', now())
            ->limit(1000)
            ->get();

        $count = 0;
        foreach ($rows as $row) {
            $user = \App\Models\User::find($row->user_id);
            if ($user) {
                $user->notify(new DatabaseNotification($row->payload));
                $count++;
            }
            $row->update(['sent_at' => now()]);
        }

        $this->info("Digest entries flushed: {$count}");
        return self::SUCCESS;
    }
}
