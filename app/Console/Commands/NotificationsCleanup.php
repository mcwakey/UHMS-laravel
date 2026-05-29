<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;

class NotificationsCleanup extends Command
{
    protected $signature = 'notifications:cleanup';

    protected $description = 'Purge old read and ancient notifications based on retention config.';

    public function handle(): int
    {
        $readDays = (int) config('notifications.cleanup.read_retention_days', 30);
        $allDays = (int) config('notifications.cleanup.all_retention_days', 180);

        $readDeleted = $readDays > 0
            ? LaravelDatabaseNotification::query()
                ->whereNotNull('read_at')
                ->where('read_at', '<', now()->subDays($readDays))
                ->delete()
            : 0;

        $oldDeleted = $allDays > 0
            ? LaravelDatabaseNotification::query()
                ->where('created_at', '<', now()->subDays($allDays))
                ->delete()
            : 0;

        $this->info("Removed {$readDeleted} read and {$oldDeleted} ancient notifications.");
        return self::SUCCESS;
    }
}
